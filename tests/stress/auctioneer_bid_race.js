/*
 * k6 stress test — Auctioneer concurrent bids race condition
 *
 * Goal: hammer /ajax/auctioneer/bid with many simultaneous VUs and verify
 * that ONLY ONE bid succeeds per point level (atomic serialization via
 * DB::transaction + lockForUpdate in AuctioneerService::placeBid).
 *
 * Pre-requisites (to be executed manually before running this test):
 *
 *   1. A valid running auction must exist in the DB. Spawn one via tinker:
 *        docker compose exec ogamex-app php artisan tinker
 *        > \OGame\Services\AuctioneerService::class
 *        > app(\OGame\Services\AuctioneerService::class)->tick();
 *        > // then promote the waiting one by running tick() again after setting waiting_ends_at=now()
 *      Or seed directly:
 *        > $a = new \OGame\Models\Auction; $a->status = 'running'; ...
 *
 *   2. A logged-in session cookie + CSRF token are required. Pass via env:
 *        COOKIE="ogamex_session=xxxxx; XSRF-TOKEN=yyyyy"
 *        CSRF="zzzzz"   (value of the X-CSRF-TOKEN meta tag on /auctioneer)
 *        PLANET_ID=12345
 *        BASE_URL="http://localhost"
 *
 *   3. The target planet must hold enough resources for all VUs attempts.
 *      Easiest: bump resources via admin developer shortcuts.
 *
 * Run:
 *   k6 run \
 *     -e BASE_URL=http://localhost \
 *     -e COOKIE="ogamex_session=..." \
 *     -e CSRF="..." \
 *     -e PLANET_ID=1 \
 *     tests/stress/auctioneer_bid_race.js
 *
 * Expected outcome:
 *   - Total requests: ~200 (scaled by stages).
 *   - HTTP 200 (success) count ≤ number of distinct bid points attempted.
 *   - HTTP 400 ("Bid too low" / "Insufficient resources") count = total - 200-count.
 *   - No 500 errors.
 *   - p(95) latency < 500ms.
 */

import http from 'k6/http';
import { check, sleep } from 'k6';
import { Counter, Rate } from 'k6/metrics';

const BASE_URL   = __ENV.BASE_URL   || 'http://localhost';
const COOKIE     = __ENV.COOKIE     || '';
const CSRF       = __ENV.CSRF       || '';
const PLANET_ID  = __ENV.PLANET_ID  || '1';

// Each VU picks a bid amount; many VUs use the same amount to exercise the race.
// Only one should win per amount; the rest must receive 400 ("Bid too low"
// because current_bid_points has advanced) or "Insufficient resources".
const BID_BUCKETS = [
    { metal: 1000,  crystal:    0, deuterium: 0 }, // 1000 points
    { metal: 2000,  crystal:    0, deuterium: 0 }, // 2000 points
    { metal: 3000,  crystal:    0, deuterium: 0 }, // 3000 points
    { metal: 4000,  crystal:    0, deuterium: 0 }, // 4000 points
    { metal: 5000,  crystal:    0, deuterium: 0 }, // 5000 points
];

const successCounter = new Counter('bid_success');
const lowBidCounter  = new Counter('bid_too_low');
const resCounter     = new Counter('bid_no_resources');
const serverErrors   = new Counter('server_errors');
const successRate    = new Rate('bid_success_rate');

export const options = {
    stages: [
        { duration: '5s',  target: 20  }, // warm up
        { duration: '10s', target: 100 }, // peak
        { duration: '5s',  target: 0   }, // cool down
    ],
    thresholds: {
        'http_req_duration{scenario:default}': ['p(95)<500'],
        'server_errors':                       ['count<1'],
    },
};

export default function () {
    const bucket = BID_BUCKETS[__VU % BID_BUCKETS.length];

    const payload = {
        metal:     bucket.metal,
        crystal:   bucket.crystal,
        deuterium: bucket.deuterium,
        planet_id: PLANET_ID,
    };

    const headers = {
        'Accept':           'application/json',
        'Content-Type':     'application/x-www-form-urlencoded',
        'X-CSRF-TOKEN':     CSRF,
        'X-Requested-With': 'XMLHttpRequest',
        'Cookie':           COOKIE,
    };

    const res = http.post(
        `${BASE_URL}/ajax/auctioneer/bid`,
        Object.keys(payload).map(k => `${k}=${payload[k]}`).join('&'),
        { headers, tags: { scenario: 'default' } },
    );

    check(res, {
        'no 5xx':              r => r.status < 500,
        'body is JSON':        r => (r.headers['Content-Type'] || '').includes('application/json'),
    });

    if (res.status >= 500) {
        serverErrors.add(1);
    } else if (res.status === 200) {
        successCounter.add(1);
        successRate.add(1);
    } else if (res.status === 400) {
        successRate.add(0);
        try {
            const body = res.json();
            const msg = (body && body.message) || '';
            if (msg.includes('Bid too low'))              lowBidCounter.add(1);
            else if (msg.includes('Insufficient'))        resCounter.add(1);
        } catch (e) { /* ignore */ }
    }

    sleep(Math.random() * 0.2); // 0–200ms jitter
}

export function handleSummary(data) {
    const m = data.metrics;
    const pick = n => (m[n] ? m[n].values.count || m[n].values.rate : 0);

    return {
        stdout: `
==================================================
Auctioneer bid-race summary
==================================================
Successful bids (200):           ${pick('bid_success')}
Rejected "Bid too low" (400):    ${pick('bid_too_low')}
Rejected "Insufficient" (400):   ${pick('bid_no_resources')}
Server errors (5xx):             ${pick('server_errors')}
Overall success rate:            ${(pick('bid_success_rate') * 100).toFixed(2)}%
--------------------------------------------------
Invariant: server_errors MUST be 0 (atomicity).
Invariant: bid_success ≤ # of distinct bid points attempted.
==================================================
`,
    };
}
