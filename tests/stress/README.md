# Stress tests

## Auctioneer bid race

File: `auctioneer_bid_race.js`

### Run

```bash
# 1. Install k6
#    https://k6.io/docs/get-started/installation/

# 2. Start the dev environment
docker compose up -d

# 3. Ensure a running auction exists in DB
docker compose exec ogamex-app php artisan tinker \
  --execute="app(\OGame\Services\AuctioneerService::class)->tick();"

# 4. Get a session cookie + CSRF token
#    - Log into http://localhost
#    - Open DevTools → Application → Cookies → copy `ogamex_session`
#    - Visit /auctioneer → View source → copy `<meta name="csrf-token">`

# 5. Run stress
k6 run \
  -e BASE_URL=http://localhost \
  -e COOKIE="ogamex_session=..." \
  -e CSRF="..." \
  -e PLANET_ID=1 \
  tests/stress/auctioneer_bid_race.js
```

### Thresholds

- `http_req_duration p(95) < 500ms`
- `server_errors < 1` — **atomicity invariant**: no 5xx at any concurrency level.

### Functional invariants

Verified by the accompanying phpunit suite (`tests/Feature/AuctioneerTest.php`):
- Only one bid succeeds per point level (`testConcurrentBidsOnlyOneWinsAtSamePointLevel`)
- Late bids extend `ends_at` (`testLateBidExtendsEndTime`)
- Prize assignment for resources / dark matter works on close (`testPrizeAssignment*`)
