@extends('ingame.layouts.main')

@section('content')

    <div id="playerprofilecomponent" class="maincontent">
        <div id="inhalt">

            <div class="tabSelection">
                <div class="tabSelectionTab profileTab active" data-target="profileOverview">{{ __('t_ingame.profile.tab_profile') }}</div>
                <div class="tabSelectionTab achievementsTab" data-target="achievementOverview">{{ __('t_ingame.profile.tab_achievements') }}</div>
                <div class="none"></div>
            </div>

            @if($achievementsVisible)
                <div id="achievementOverview" class="contentRS hidden">
                    @include('ingame.playerprofile._achievements')
                </div>
            @endif

            @if(!$visible)
                <div id="profileOverview" class="contentRS">
                    <div class="mainRS">
                        <div class="profileName">
                            <span class="playername">{!! $targetPlayer->getUsername() !!}</span>
                        </div>
                        <div class="profileHolder">
                            <div class="profileNotVisible">
                                {{ __('t_ingame.profile.not_visible') }}
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div id="profileOverview" class="contentRS">

                    {{-- Pannello "tag disponibili" (visibile solo in edit mode) --}}
                    @if($isOwner)
                        <div class="editHolder" id="editHolder">
                            @foreach($availableTags as $item)
                                <div class="profileTag" data-type="{{ $item['tag']->value }}">
                                    {{ $item['label'] }}
                                </div>
                            @endforeach
                            <div class="overlay" hidden>
                                <span class="close closeOverlay">✗</span>
                            </div>
                        </div>

                        {{-- Template entries (non visibili, usati da JS per add) --}}
                        <template id="entryTemplates">
                            @foreach($availableTags as $item)
                                <div data-template-for="{{ $item['tag']->value }}">
                                    @include('ingame.playerprofile._entry', ['entry' => $item['entry']])
                                </div>
                            @endforeach
                        </template>
                    @endif

                    {{-- Header (Profilo globale + edit toolbar) --}}
                    <div class="headerRS">
                        <div class="headerContainer">
                            @if($isOwner)
                                <div class="globalProfileContainer">
                                    <span>{{ __('t_ingame.profile.global_profile') }}:</span>
                                    <label class="toggle-switch">
                                        <input type="checkbox" id="globalProfile" data-field="global_profile" @checked($targetPlayer->getUser()->global_profile ?? false)>
                                        <span class="toggle-slider"></span>
                                    </label>
                                    <span class="info tooltip" title="{{ __('t_ingame.profile.global_profile_info') }}">
                                        <span class="icon icon_info">?</span>
                                    </span>
                                </div>
                                <div class="globalProfileContainer editToolbar">
                                    <span class="edit-icon editProfile" id="editProfileIcon" title="{{ __('t_ingame.profile.edit_profile') }}">✎</span>
                                    <button type="button" class="custom_btn green saveProfile" id="saveProfileBtn" hidden>{{ __('t_ingame.profile.save') }}</button>
                                    <button type="button" class="custom_btn red cancelEdit" id="cancelEditBtn" hidden>✗</button>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="mainRS">

                        {{-- Visibility toggles (solo proprietario) --}}
                        @if($isOwner)
                            <div class="visibilityHolder">
                                <div class="toggleHolder">
                                    <label class="toggle-switch">
                                        <input type="checkbox" id="profileVisibility" data-field="profile_visible" @checked($targetPlayer->getUser()->profile_visible ?? true)>
                                        <span class="toggle-slider"></span>
                                    </label>
                                    <span>{{ __('t_ingame.profile.profile_visible') }}</span>
                                </div>
                                <div class="toggleHolder">
                                    <label class="toggle-switch">
                                        <input type="checkbox" id="achievementsVisibility" data-field="achievements_visible" @checked($targetPlayer->getUser()->achievements_visible ?? true)>
                                        <span class="toggle-slider"></span>
                                    </label>
                                    <span>{{ __('t_ingame.profile.achievements_visible') }}</span>
                                </div>
                            </div>
                        @endif

                        {{-- Nome giocatore --}}
                        @php $allianceTag = optional($targetPlayer->getUser()->alliance)->alliance_tag; @endphp
                        <div class="profileName">
                            @if(!empty($allianceTag))
                                <span class="alliancetag">
                                    <span class="ally-tag">[{{ $allianceTag }}]</span>
                                </span>
                            @endif
                            <span class="playername">{!! $targetPlayer->getUsername() !!}</span>
                            @if($isOwner)
                                <a href="{{ route('changenick.overlay') }}"
                                   class="overlay editNickname"
                                   data-overlay-title="{{ __('t_ingame.layout.change_player_name') }}"
                                   data-overlay-popup-width="400"
                                   data-overlay-popup-height="200"
                                   title="{{ __('t_ingame.layout.change_player_name') }}">✎</a>
                            @endif
                        </div>

                        {{-- Avatar + colonna info --}}
                        <div class="profileHolder">
                            <div class="avatarHolder">
                                @php
                                    // Avatar selezionato (DOM 1:1 OGame): la classe macchina applica
                                    // la background-image via regola CSS in playerprofile_avatars.css.
                                    $selectedAvatar = $targetPlayer->getUser()->profile_avatar ?? '';
                                    $avatarClass = $selectedAvatar !== '' ? $selectedAvatar : 'default';
                                @endphp
                                <profile-picture class="{{ $avatarClass }} sq200"></profile-picture>
                            </div>
                            <div class="profilePageInfo" id="profilePageInfo" data-section="profile">
                                @foreach($profileEntries as $entry)
                                    @include('ingame.playerprofile._entry', ['entry' => $entry])
                                @endforeach
                            </div>
                        </div>

                        {{-- Sezione highscore inferiore --}}
                        <div class="moreInfoHolder" id="moreInfoHolder" data-section="moreInfo">
                            @foreach($moreInfoEntries as $entry)
                                @include('ingame.playerprofile._entry', ['entry' => $entry])
                            @endforeach
                        </div>

                    </div>

                    <div class="footerRS"></div>
                </div>
            @endif

        </div>
    </div>

    {{-- ── JS Tab Trofei (anche per non-owner se achievements_visible) ── --}}
    @if($achievementsVisible)
    <script>
    (function () {
        'use strict';
        // Top-level tab switch (Profilo / Trofei)
        var profileOv = document.getElementById('profileOverview');
        var achievOv = document.getElementById('achievementOverview');
        document.querySelectorAll('.tabSelectionTab').forEach(function (tab) {
            tab.addEventListener('click', function () {
                if (tab.classList.contains('disabled')) return;
                document.querySelectorAll('.tabSelectionTab').forEach(function (t) { t.classList.remove('active'); });
                tab.classList.add('active');
                var target = tab.getAttribute('data-target');
                if (profileOv) profileOv.classList.toggle('hidden', target !== 'profileOverview');
                if (achievOv) achievOv.classList.toggle('hidden', target !== 'achievementOverview');
            });
        });

        // ── DOM 1:1 OGame: handler legati ai nomi globali invocati dagli onclick inline ──

        // Sub-category switch: chiamata da onclick="changeAchievementCategory(this)"
        window.changeAchievementCategory = function (el) {
            var key = el.getAttribute('data-achievement-category-id');
            document.querySelectorAll('#achievementsOverviewCategories .achievementCategory').forEach(function (c) {
                c.classList.toggle('active', c === el);
            });
            ['unlocks', 'avatars', 'spaceObjectSkins', 'titles'].forEach(function (k) {
                var n = document.getElementById('achievementContentList_' + k);
                if (n) n.classList.toggle('hidden', k !== key);
            });
        };

        // Tier switch dentro un achievement: chiamata da onclick="showAchievementTier('1000100', 1)"
        window.showAchievementTier = function (extId, tier) {
            var card = document.getElementById('achievementOverviewAchievementHolder_' + extId);
            if (!card) return;
            card.querySelectorAll('.achievementOverviewTierSelectionContainer .custom_btn').forEach(function (b, idx) {
                b.classList.toggle('selected', (idx + 1) === tier);
            });
            card.querySelectorAll('.achievementOverviewTiersContainer > .achievementTierContainer').forEach(function (c) {
                var matches = c.classList.contains('tier_' + tier);
                c.classList.toggle('visible', matches);
            });
        };

        // Filtri Riepilogo: onclick="filterAchievements(this, 'all'|'unfinished'|'finished')"
        window.filterAchievements = function (el, f) {
            document.querySelectorAll('#achievementOverviewAchievementFilters .achievementFilteringOptions .custom_btn').forEach(function (b) {
                b.classList.remove('active');
            });
            el.classList.add('active');
            document.querySelectorAll('#achievementContentList_unlocks .achievementOverviewAchievementHolder').forEach(function (card) {
                var tiers = card.querySelectorAll('.achievementTierContainer');
                var unlockedCount = card.querySelectorAll('.achievementTierContainer.unlocked').length;
                var total = tiers.length;
                var isFinished = (total > 0 && unlockedCount >= total);
                var visible = (f === 'all') || (f === 'unfinished' && !isFinished) || (f === 'finished' && isFinished);
                card.classList.toggle('hidden', !visible);
            });
        };

        // Expand / collapse all (mostra tutti i tier vs solo quello selezionato)
        window.expandAllAchievementTiers = function () {
            document.querySelectorAll('#achievementContentList_unlocks .achievementOverviewAchievementHolder .achievementTierContainer').forEach(function (c) {
                c.classList.add('visible');
            });
            var e = document.getElementById('achievementOverviewExpandAllBtn');
            var c = document.getElementById('achievementOverviewCollapseAllBtn');
            if (e) e.style.display = 'none';
            if (c) c.style.display = '';
        };
        window.collapseAllAchievementTiers = function () {
            document.querySelectorAll('#achievementContentList_unlocks .achievementOverviewAchievementHolder').forEach(function (card) {
                var sel = card.querySelector('.achievementOverviewTierSelectionContainer .custom_btn.selected');
                var idx = sel ? Array.prototype.indexOf.call(sel.parentNode.parentNode.querySelectorAll('.custom_btn'), sel) + 1 : 1;
                card.querySelectorAll('.achievementTierContainer').forEach(function (c) {
                    c.classList.toggle('visible', c.classList.contains('tier_' + idx));
                });
            });
            var e = document.getElementById('achievementOverviewExpandAllBtn');
            var c = document.getElementById('achievementOverviewCollapseAllBtn');
            if (e) e.style.display = '';
            if (c) c.style.display = 'none';
        };

        @if($isOwner)
        // Selezione reward dai cataloghi avatar / skin / titoli (solo se non locked).
        var rewardEndpoint = @json(route('playerprofile.selectreward'));
        var csrfRT = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        function applyReward(holder, type, machine) {
            return fetch(rewardEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfRT,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ type: type, machine_name: machine })
            }).then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            }).then(function (data) {
                if (!data.success) throw new Error(data.error || 'fail');
                // Rimuovi .selected da tutti i sibling dello stesso tipo e aggiungi al cliccato.
                var listId = type === 'avatar' ? 'achievementContentList_avatars'
                            : type === 'skin'   ? 'achievementContentList_spaceObjectSkins'
                            :                     'achievementContentList_titles';
                var list = document.getElementById(listId);
                if (list) list.querySelectorAll('.selected').forEach(function (el) { el.classList.remove('selected'); });
                holder.classList.add('selected');
            }).catch(function () { /* silent */ });
        }

        // Avatar holders
        document.querySelectorAll('#achievementContentList_avatars .achievementOverviewProfilePictureHolder').forEach(function (h) {
            h.addEventListener('click', function () {
                var pp = h.querySelector('profile-picture');
                if (!pp || pp.classList.contains('locked')) return;
                applyReward(h, 'avatar', h.getAttribute('data-avatar-id'));
            });
        });
        // Skin holders
        document.querySelectorAll('#achievementContentList_spaceObjectSkins .achievementOverviewSpaceObjectSkinHolder').forEach(function (h) {
            h.addEventListener('click', function () {
                var s = h.querySelector('space-object-skin');
                if (!s || s.classList.contains('locked')) return;
                applyReward(h, 'skin', h.getAttribute('data-space-object-skin-id'));
            });
        });
        // Title holders
        document.querySelectorAll('#achievementContentList_titles .achievementOverviewProfileTitleHolder').forEach(function (h) {
            h.addEventListener('click', function () {
                if (h.classList.contains('locked')) return;
                applyReward(h, 'title', h.getAttribute('data-title-id'));
            });
        });
        @endif
    })();
    </script>
    @endif

    @if($isOwner && $visible)
        <script>
        (function () {
            'use strict';
            var visibilityEndpoint = @json(route('playerprofile.visibility'));
            var tagsEndpoint = @json(route('playerprofile.tags'));
            var genderEndpoint = @json(route('playerprofile.gender'));
            var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            function postJson(url, payload) {
                return fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload)
                }).then(function (r) {
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return r.json();
                }).then(function (data) {
                    if (!data.success) throw new Error(data.error || 'fail');
                    return data;
                });
            }

            // ── Toggle visibility ────────────────────────────────────────
            function bindToggle(id) {
                var el = document.getElementById(id);
                if (!el) return;
                var prev = el.checked;
                el.addEventListener('change', function () {
                    var field = el.getAttribute('data-field');
                    var value = el.checked;
                    el.disabled = true;
                    postJson(visibilityEndpoint, { field: field, value: value })
                        .then(function () { prev = value; })
                        .catch(function () { el.checked = prev; })
                        .finally(function () { el.disabled = false; });
                });
            }
            bindToggle('profileVisibility');
            bindToggle('achievementsVisibility');
            bindToggle('globalProfile');

            // ── Title M/F selector (sempre attivo, anche fuori edit mode) ──
            document.querySelectorAll('.titleTypeSelection').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var gender = btn.getAttribute('data-gender');
                    if (!gender || btn.classList.contains('active')) return;
                    document.querySelectorAll('.titleTypeSelection').forEach(function (b) {
                        b.classList.toggle('active', b.getAttribute('data-gender') === gender);
                    });
                    postJson(genderEndpoint, { gender: gender }).catch(function () {
                        // rollback visivo in caso di errore
                        document.querySelectorAll('.titleTypeSelection').forEach(function (b) {
                            b.classList.toggle('active', b.getAttribute('data-gender') !== gender);
                        });
                    });
                });
            });

            // ── Edit mode ────────────────────────────────────────────────
            var profileOverview = document.getElementById('profileOverview');
            var editIcon = document.getElementById('editProfileIcon');
            var saveBtn = document.getElementById('saveProfileBtn');
            var cancelBtn = document.getElementById('cancelEditBtn');
            var editHolder = document.getElementById('editHolder');
            var profilePageInfo = document.getElementById('profilePageInfo');
            var moreInfoHolder = document.getElementById('moreInfoHolder');
            var entryTemplates = document.getElementById('entryTemplates');
            var pillLabels = {};
            var snapshotProfileHTML = '';
            var snapshotMoreInfoHTML = '';
            var snapshotEditHolderHTML = '';

            // Cache delle label originali (per ricostruire pillole su remove)
            editHolder.querySelectorAll('.profileTag').forEach(function (p) {
                pillLabels[p.getAttribute('data-type')] = p.textContent.trim();
            });

            function setEditMode(on) {
                if (on) profileOverview.classList.add('editing');
                else profileOverview.classList.remove('editing');
                if (editIcon) editIcon.hidden = on;
                if (saveBtn) saveBtn.hidden = !on;
                if (cancelBtn) cancelBtn.hidden = !on;
                if (!on) clearActiveSelection();
            }
            function snapshot() {
                snapshotProfileHTML = profilePageInfo.innerHTML;
                snapshotMoreInfoHTML = moreInfoHolder.innerHTML;
                snapshotEditHolderHTML = editHolder.innerHTML;
            }
            function restore() {
                profilePageInfo.innerHTML = snapshotProfileHTML;
                moreInfoHolder.innerHTML = snapshotMoreInfoHTML;
                editHolder.innerHTML = snapshotEditHolderHTML;
                bindRowEvents();
                bindPillEvents();
            }

            // ── Slot-based UX: click pill → mostra slot, click slot → posiziona ──
            var activePill = null;

            function clearActiveSelection() {
                if (activePill) activePill.classList.remove('active');
                activePill = null;
                clearDroppable();
            }

            function clearDroppable() {
                document.querySelectorAll('.profileEntry.droppable').forEach(function (s) {
                    s.classList.remove('droppable');
                });
            }

            function highlightAllEmptySlots() {
                clearDroppable();
                [profilePageInfo, moreInfoHolder].forEach(function (container) {
                    Array.from(container.children).forEach(function (c) {
                        if (c.classList && c.classList.contains('profileEntry') && c.classList.contains('empty')) {
                            c.classList.add('droppable');
                            c.removeEventListener('click', onSlotClick);
                            c.addEventListener('click', onSlotClick);
                        }
                    });
                });
            }

            function onPillClick(e) {
                var pill = e.currentTarget;
                if (activePill === pill) {
                    clearActiveSelection();
                    return;
                }
                clearActiveSelection();
                activePill = pill;
                pill.classList.add('active');
                highlightAllEmptySlots();
            }

            function onSlotClick(e) {
                e.stopPropagation();
                if (!activePill) return;
                var slot = e.currentTarget;
                var type = activePill.getAttribute('data-type');
                var tpl = entryTemplates ? entryTemplates.content.querySelector('[data-template-for="'+type+'"]') : null;
                if (!tpl) { clearActiveSelection(); return; }
                var entryNode = tpl.firstElementChild ? tpl.firstElementChild.cloneNode(true) : null;
                if (!entryNode) { clearActiveSelection(); return; }
                // Sostituisce lo slot vuoto con la nuova entry (mantiene il numero totale di slot fisso).
                slot.replaceWith(entryNode);
                bindRowEvents();
                activePill.remove();
                clearActiveSelection();
            }

            function onRemoveClick(e) {
                e.stopPropagation();
                var entryEl = e.currentTarget.closest('.profileEntry');
                if (!entryEl) return;
                var type = entryEl.getAttribute('data-type');
                if (!type) return;

                var label = pillLabels[type];
                if (!label) {
                    var heading = entryEl.querySelector('.profileHeading');
                    label = heading ? heading.textContent.replace(':', '').trim() : type;
                    pillLabels[type] = label;
                }

                var pill = document.createElement('div');
                pill.className = 'profileTag';
                pill.setAttribute('data-type', type);
                pill.textContent = label;
                pill.addEventListener('click', onPillClick);
                editHolder.insertBefore(pill, editHolder.querySelector('.overlay') || null);

                // Rimpiazza la entry con uno slot vuoto (mantiene total slot fisso 9/12).
                var emptySlot = document.createElement('div');
                emptySlot.className = 'profileEntry empty';
                entryEl.replaceWith(emptySlot);
            }

            function bindRowEvents() {
                document.querySelectorAll('#profilePageInfo .removeEntry, #moreInfoHolder .removeEntry').forEach(function (btn) {
                    btn.removeEventListener('click', onRemoveClick);
                    btn.addEventListener('click', onRemoveClick);
                });
            }
            function bindPillEvents() {
                editHolder.querySelectorAll('.profileTag').forEach(function (pill) {
                    pill.removeEventListener('click', onPillClick);
                    pill.addEventListener('click', onPillClick);
                });
            }

            if (editIcon) {
                editIcon.addEventListener('click', function () {
                    snapshot();
                    setEditMode(true);
                    bindPillEvents();
                    bindRowEvents();
                });
            }
            if (cancelBtn) {
                cancelBtn.addEventListener('click', function () {
                    restore();
                    setEditMode(false);
                });
            }
            if (saveBtn) {
                saveBtn.addEventListener('click', function () {
                    clearActiveSelection();
                    var profileTags = Array.from(profilePageInfo.querySelectorAll('.profileEntry:not(.empty)'))
                        .map(function (e) { return e.getAttribute('data-type'); })
                        .filter(Boolean);
                    var moreInfoTags = Array.from(moreInfoHolder.querySelectorAll('.profileEntry:not(.empty)'))
                        .map(function (e) { return e.getAttribute('data-type'); })
                        .filter(Boolean);
                    saveBtn.disabled = true;
                    postJson(tagsEndpoint, { profile: profileTags, moreInfo: moreInfoTags })
                        .then(function () { window.location.reload(); })
                        .catch(function () {
                            saveBtn.disabled = false;
                            alert(@json(__('t_ingame.profile.save_error')));
                        });
                });
            }
        })();
        </script>
    @endif

@endsection
