/**
 * Clausen v1.1 — Theme JavaScript
 * Terres Rouges · Twin Cities Luxembourg American Society
 */
(function () {
  'use strict';

  // ── Utilities ──────────────────────────────────────────────────────────────
  const qs  = (sel, ctx = document) => ctx.querySelector(sel);
  const qsa = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  // ── Mobile navigation ──────────────────────────────────────────────────────
  function initMobileNav() {
    const hamburger = qs('.tclas-hamburger');
    const drawer    = qs('.tclas-nav-drawer');
    if (!hamburger || !drawer) return;

    const iconMenu  = qs('.tclas-hamburger__menu',  hamburger);
    const iconClose = qs('.tclas-hamburger__close', hamburger);

    function openMenu() {
      drawer.classList.add('is-open');
      hamburger.setAttribute('aria-expanded', 'true');
      if (iconMenu)  iconMenu.style.display  = 'none';
      if (iconClose) iconClose.style.display = 'inline';
    }

    function closeMenu() {
      drawer.classList.remove('is-open');
      hamburger.setAttribute('aria-expanded', 'false');
      if (iconMenu)  iconMenu.style.display  = '';
      if (iconClose) iconClose.style.display = 'none';
    }

    hamburger.addEventListener('click', () => {
      drawer.classList.contains('is-open') ? closeMenu() : openMenu();
    });

    // Close when a nav link is tapped
    qsa('a', drawer).forEach(link => link.addEventListener('click', closeMenu));

    // Close on outside click
    document.addEventListener('click', (e) => {
      if (!hamburger.contains(e.target) && !drawer.contains(e.target)) {
        closeMenu();
      }
    });

    // Close on Escape
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeMenu();
    });
  }

  // ── Member hub sidebar (mobile) ───────────────────────────────────────────
  function initHubSidebar() {
    const sidebar   = qs('.tclas-hub-sidebar');
    const toggle    = qs('.tclas-hub-mobile-toggle');
    const backdrop  = qs('.tclas-hub-sidebar-backdrop');
    if (!sidebar || !toggle) return;

    function getFocusable() {
      return qsa('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])', sidebar);
    }

    function trapFocus(e) {
      if (e.key !== 'Tab') return;
      const focusable = getFocusable();
      if (!focusable.length) return;
      const first = focusable[0];
      const last  = focusable[focusable.length - 1];
      if (e.shiftKey) {
        if (document.activeElement === first) { e.preventDefault(); last.focus(); }
      } else {
        if (document.activeElement === last) { e.preventDefault(); first.focus(); }
      }
    }

    function open() {
      sidebar.classList.add('is-open');
      sidebar.setAttribute('role', 'dialog');
      sidebar.setAttribute('aria-modal', 'true');
      backdrop && backdrop.classList.add('is-visible');
      document.body.style.overflow = 'hidden';
      // Move focus into sidebar
      var first = getFocusable()[0];
      if (first) first.focus();
      sidebar.addEventListener('keydown', trapFocus);
    }

    function close() {
      sidebar.classList.remove('is-open');
      sidebar.removeAttribute('role');
      sidebar.removeAttribute('aria-modal');
      backdrop && backdrop.classList.remove('is-visible');
      document.body.style.overflow = '';
      sidebar.removeEventListener('keydown', trapFocus);
      toggle.focus();
    }

    toggle.addEventListener('click', open);
    backdrop && backdrop.addEventListener('click', close);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && sidebar.classList.contains('is-open')) close(); });
  }

  // ── Renew banner dismiss ───────────────────────────────────────────────────
  function initRenewBanner() {
    const btn = qs('.tclas-renew-banner__dismiss');
    if (!btn) return;
    btn.addEventListener('click', () => {
      const banner = btn.closest('.tclas-renew-banner');
      if (banner) banner.remove();
      // Remember dismissal for this session
      try { sessionStorage.setItem('tclas_renew_dismissed', '1'); } catch(e) {}
    });
    // Re-apply dismissal
    try {
      if (sessionStorage.getItem('tclas_renew_dismissed')) {
        const banner = qs('.tclas-renew-banner');
        if (banner) banner.remove();
      }
    } catch(e) {}
  }

  // ── Referral URL copy ─────────────────────────────────────────────────────
  function initReferralCopy() {
    qsa('.tclas-referral-copy-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const url = btn.dataset.url || qs('.tclas-referral-card__url')?.textContent?.trim();
        if (!url) return;

        navigator.clipboard.writeText(url).then(() => {
          const original = btn.textContent;
          btn.textContent = 'Copied!';
          btn.classList.add('is-copied');
          setTimeout(() => {
            btn.textContent = original;
            btn.classList.remove('is-copied');
          }, 2000);

          // Track copy event server-side
          if (typeof tclasData !== 'undefined' && tclasData.isLoggedIn) {
            const fd = new FormData();
            fd.append('action', 'tclas_referral_copy');
            fd.append('nonce',  tclasData.nonce);
            fetch(tclasData.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
              .catch(() => {}); // Fire-and-forget; ignore errors
          }
        }).catch(() => {
          // Fallback
          const ta = document.createElement('textarea');
          ta.value = url;
          ta.style.position = 'fixed';
          ta.style.opacity = '0';
          document.body.appendChild(ta);
          ta.focus(); ta.select();
          document.execCommand('copy');
          document.body.removeChild(ta);
        });
      });
    });
  }

  // ── Scroll fade-in ────────────────────────────────────────────────────────
  function initScrollReveal() {
    if (!('IntersectionObserver' in window)) return;
    const items = qsa('[data-reveal]');
    if (!items.length) return;

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12 });

    items.forEach(el => {
      el.style.opacity = '0';
      el.style.transform = 'translateY(18px)';
      el.style.transition = 'opacity 0.45s ease, transform 0.45s ease';
      observer.observe(el);
    });

    // Add is-visible style
    const style = document.createElement('style');
    style.textContent = '[data-reveal].is-visible { opacity: 1 !important; transform: translateY(0) !important; }';
    document.head.appendChild(style);
  }

  // ── National Day season detection ─────────────────────────────────────────
  function initNationalDaySeason() {
    if (typeof tclasData === 'undefined' || !tclasData.nationalDay) return;
    const { isNationalDaySeason } = tclasData.nationalDay;
    if (isNationalDaySeason) {
      document.body.classList.add('is-national-day-season');
    }
  }

  // ── Directory filter buttons ───────────────────────────────────────────────
  function initDirectoryFilters() {
    qsa('.tclas-filter-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const group = btn.closest('.tclas-filter-bar');
        if (!group) return;
        qsa('.tclas-filter-btn', group).forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        // Dispatch event for any JS filtering logic
        document.dispatchEvent(new CustomEvent('tclasFilter', {
          detail: { filter: btn.dataset.filter }
        }));
      });
    });
  }

  // ── Primary navigation dropdown menu ──────────────────────────────────────
  function initDropdowns() {
    // Only initialize on desktop (992px+)
    if (window.innerWidth < 992) return;

    const parentItems = qsa('.tclas-nav__item.has-dropdown');

    parentItems.forEach(item => {
      const link = qs('.tclas-nav__link', item);
      const dropdown = qs('.tclas-nav__dropdown', item);
      if (!link || !dropdown) return;

      // Initialize aria-expanded to false
      link.setAttribute('aria-expanded', 'false');

      // Hover to show/hide dropdown
      item.addEventListener('mouseenter', () => {
        link.setAttribute('aria-expanded', 'true');
      });

      item.addEventListener('mouseleave', () => {
        link.setAttribute('aria-expanded', 'false');
      });

      // Keyboard navigation
      link.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          const isExpanded = link.getAttribute('aria-expanded') === 'true';
          link.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
        }
        // Arrow down to focus first dropdown item
        if (e.key === 'ArrowDown') {
          e.preventDefault();
          link.setAttribute('aria-expanded', 'true');
          const firstLink = qs('a', dropdown);
          if (firstLink) firstLink.focus();
        }
      });

      // Handle links inside dropdown
      const dropdownLinks = qsa('a', dropdown);
      dropdownLinks.forEach((dropLink, idx) => {
        dropLink.addEventListener('keydown', (e) => {
          // Arrow down to next item
          if (e.key === 'ArrowDown' && idx < dropdownLinks.length - 1) {
            e.preventDefault();
            dropdownLinks[idx + 1].focus();
          }
          // Arrow up to previous item or parent link
          if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (idx > 0) {
              dropdownLinks[idx - 1].focus();
            } else {
              link.focus();
            }
          }
          // Escape to close and refocus parent
          if (e.key === 'Escape') {
            e.preventDefault();
            link.setAttribute('aria-expanded', 'false');
            link.focus();
          }
        });
      });
    });

    // Close dropdowns on click outside
    document.addEventListener('click', (e) => {
      const nav = qs('.tclas-header__desktop-nav');
      if (!nav || nav.contains(e.target)) return;

      parentItems.forEach(item => {
        const link = qs('.tclas-nav__link', item);
        if (link) link.setAttribute('aria-expanded', 'false');
      });
    });
  }

  // ── Newsletter secondary nav — mobile topics dropdown ────────────────────
  function initNewsletterNav() {
    const btn = qs('.topics-dropdown-button');
    if (!btn) return;

    const menu    = qs('.topics-dropdown-menu');
    const chevron = qs('.chevron-icon', btn);

    function openDropdown() {
      menu.removeAttribute('hidden');
      chevron.classList.add('open');
      btn.setAttribute('aria-expanded', 'true');
    }

    function closeDropdown() {
      menu.setAttribute('hidden', '');
      chevron.classList.remove('open');
      btn.setAttribute('aria-expanded', 'false');
    }

    btn.addEventListener('click', () => {
      btn.getAttribute('aria-expanded') === 'true' ? closeDropdown() : openDropdown();
    });

    document.addEventListener('click', (e) => {
      const wrap = btn.closest('.newsletter-topics-dropdown');
      if (wrap && !wrap.contains(e.target)) closeDropdown();
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeDropdown();
    });
  }

  // ── Member nav — mobile dropdown toggle ────────────────────────────────────
  function initMemberNav() {
    const btn = qs('.tclas-member-nav__toggle');
    if (!btn) return;

    const menu = qs('#member-nav-dropdown');

    function open() {
      menu.removeAttribute('hidden');
      btn.setAttribute('aria-expanded', 'true');
    }

    function close() {
      menu.setAttribute('hidden', '');
      btn.setAttribute('aria-expanded', 'false');
    }

    btn.addEventListener('click', () => {
      btn.getAttribute('aria-expanded') === 'true' ? close() : open();
    });

    document.addEventListener('click', (e) => {
      if (!btn.closest('.tclas-member-nav__mobile').contains(e.target)) close();
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') close();
    });
  }

  // ── Newsletter sticky sub-nav scroll tracker ──────────────────────────────
  function initNlSubnav() {
    const nav = qs('.tclas-nl-subnav');
    if (!nav) return;

    const links    = qsa('.tclas-nl-subnav__link[data-nl-section]', nav);
    const sections = [];

    links.forEach(link => {
      const id = link.dataset.nlSection;
      const el = document.getElementById(id);
      if (el) sections.push({ id, el, link });
    });

    if (!sections.length) return;

    const ACTIVE = 'tclas-nl-subnav__link--active';

    function setActive(id) {
      links.forEach(l => l.classList.remove(ACTIVE));
      const match = sections.find(s => s.id === id);
      if (match) match.link.classList.add(ACTIVE);
    }

    if ('IntersectionObserver' in window) {
      const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            setActive(entry.target.id);
          }
        });
      }, { rootMargin: '-20% 0px -60% 0px', threshold: 0 });

      sections.forEach(s => observer.observe(s.el));
    }

    // Set initial active state on load.
    setActive(sections[0].id);
  }

  // ── Smooth scroll for anchor links ────────────────────────────────────────
  function initSmoothScroll() {
    qsa('a[href^="#"]').forEach(link => {
      link.addEventListener('click', (e) => {
        const target = qs(link.getAttribute('href'));
        if (!target) return;
        e.preventDefault();
        // Account for newsletter sub-nav if present
        const nlNav  = qs('.newsletter-nav');
        const offset = 80 + (nlNav ? nlNav.offsetHeight : 0);
        const top = target.getBoundingClientRect().top + window.scrollY - offset;
        window.scrollTo({ top, behavior: 'smooth' });
        target.setAttribute('tabindex', '-1');
        target.focus({ preventScroll: true });
      });
    });
  }

  // ── Lëtzebuergesch tooltip init ───────────────────────────────────────────
  function initLtzTooltips() {
    // abbr.ltz elements get Bootstrap tooltips if available, otherwise title fallback
    qsa('abbr.ltz').forEach(el => {
      if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        new bootstrap.Tooltip(el, { placement: 'top' });
      }
    });
  }

  // ── Connections panel ─────────────────────────────────────────────────────
  function initConnectionsPanel() {
    const panel = qs('#tclas-connections-panel');
    if (!panel) return;

    // Mark all visible connections as "seen" after a short delay,
    // so members can read them before the badge clears.
    const newCount = parseInt(panel.dataset.new || '0', 10);
    if (newCount > 0 && typeof tclasData !== 'undefined' && tclasData.isLoggedIn) {
      setTimeout(() => {
        const fd = new FormData();
        fd.append('action', 'tclas_mark_connections_seen');
        fd.append('nonce', tclasData.nonce);
        fetch(tclasData.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
          .then(() => {
            const badge = qs('.tclas-conn-badge', panel);
            if (badge) badge.remove();
            panel.classList.remove('tclas-conn-panel--has-new');
            qsa('.tclas-conn-card--new', panel).forEach(c => c.classList.remove('tclas-conn-card--new'));
          })
          .catch(() => {});
      }, 4000); // 4-second read window before clearing badge
    }

    // Dismiss individual connection cards.
    panel.addEventListener('click', (e) => {
      const btn = e.target.closest('.tclas-conn-dismiss');
      if (!btn) return;

      const otherId = btn.dataset.otherId;
      const card    = btn.closest('.tclas-conn-card');
      if (!card || !otherId) return;

      // Optimistic removal.
      card.style.transition = 'opacity .25s, transform .25s';
      card.style.opacity    = '0';
      card.style.transform  = 'translateX(8px)';

      setTimeout(() => {
        card.remove();

        // If no cards remain, reload the panel content to show empty state.
        const remaining = qsa('.tclas-conn-card', panel);
        if (remaining.length === 0) {
          window.location.reload();
        }
      }, 280);

      // Persist dismissal server-side (fire-and-forget).
      if (typeof tclasData !== 'undefined' && tclasData.isLoggedIn) {
        const fd = new FormData();
        fd.append('action',        'tclas_dismiss_connection');
        fd.append('nonce',         tclasData.nonce);
        fd.append('other_user_id', otherId);
        fetch(tclasData.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
          .catch(() => {});
      }
    });
  }

  // ── My Luxembourg Story form — repeater fields ────────────────────────────
  function initMyStoryForm() {
    const form = qs('#tclas-my-story-form');
    if (!form) return;

    // ── Generic repeater: add row ──────────────────────────────────────
    qsa('.tclas-repeater-add').forEach(btn => {
      btn.addEventListener('click', () => {
        const listId = btn.dataset.target;
        const list   = qs('#' + listId);
        if (!list) return;

        const row = document.createElement('div');
        row.className = 'tclas-repeater-row';

        const input = document.createElement('input');
        input.type         = 'text';
        input.name         = btn.dataset.name;
        input.className    = 'tclas-story-input';
        input.placeholder  = btn.dataset.placeholder || '';
        input.autocomplete = 'off';
        if (btn.dataset.list) {
          input.setAttribute('list', btn.dataset.list);
        }
        input.setAttribute('aria-label', input.placeholder);

        const removeBtn = document.createElement('button');
        removeBtn.type      = 'button';
        removeBtn.className = 'tclas-repeater-remove';
        removeBtn.setAttribute('aria-label', 'Remove');
        removeBtn.textContent = '×';

        row.appendChild(input);
        row.appendChild(removeBtn);
        list.appendChild(row);
        input.focus();
      });
    });

    // ── Generic repeater: remove row (delegated) ────────────────────────
    qsa('.tclas-repeater-list').forEach(list => {
      list.addEventListener('click', (e) => {
        const btn = e.target.closest('.tclas-repeater-remove');
        if (!btn) return;
        const row = btn.closest('.tclas-repeater-row');
        if (row) {
          row.style.opacity = '0';
          row.style.transition = 'opacity .2s';
          setTimeout(() => row.remove(), 220);
        }
      });
    });

    // ── Lineage card repeater ──────────────────────────────────────────
    initLineageRepeater();

    // ── Save feedback: swap button text briefly ────────────────────────
    form.addEventListener('submit', () => {
      const submitBtn = qs('[type="submit"]', form);
      if (submitBtn) {
        submitBtn.textContent = (tclasData && tclasData.strings && tclasData.strings.connSaving)
          ? tclasData.strings.connSaving
          : 'Saving…';
        submitBtn.disabled = true;
      }
    });
  }

  // ── Lineage card repeater (commune → surnames) ──────────────────────────
  function initLineageRepeater() {
    const lineageList = qs('#tclas-lineage-list');
    const addCardBtn  = qs('#tclas-lineage-add-card');
    if (!lineageList) return;

    // Track next card index for unique name attributes.
    var cardIndex = lineageList.querySelectorAll('.tclas-lineage-card').length;

    // ── Add new lineage card ──────────────────────────────────────────
    if (addCardBtn) {
      addCardBtn.addEventListener('click', () => {
        var idx = cardIndex++;
        var card = document.createElement('div');
        card.className = 'tclas-lineage-card';
        card.setAttribute('data-card-index', idx);

        card.innerHTML =
          '<div class="tclas-lineage-card__header">' +
            '<input type="text" name="tclas_lineage_commune[]"' +
            ' class="tclas-story-input tclas-lineage-commune-input"' +
            ' placeholder="e.g. Remich" autocomplete="off"' +
            ' list="tclas-commune-options"' +
            ' aria-label="Ancestral commune">' +
            '<button type="button" class="tclas-repeater-remove tclas-lineage-remove-card"' +
            ' aria-label="Remove this lineage">\u00d7</button>' +
          '</div>' +
          '<div class="tclas-lineage-card__surnames">' +
            '<div class="tclas-repeater-row">' +
              '<input type="text" name="tclas_lineage_surnames[' + idx + '][]"' +
              ' class="tclas-story-input"' +
              ' placeholder="e.g. Kieffer" autocomplete="off"' +
              ' aria-label="Paired surname">' +
            '</div>' +
          '</div>' +
          '<button type="button" class="btn btn-sm btn-link tclas-lineage-add-surname">' +
            '+ Add surname' +
          '</button>';

        lineageList.appendChild(card);
        card.querySelector('.tclas-lineage-commune-input').focus();
      });
    }

    // ── Delegated events on the lineage list ──────────────────────────
    lineageList.addEventListener('click', (e) => {
      var target = e.target;

      // ── Remove entire card ──────────────────────────────────────────
      if (target.closest('.tclas-lineage-remove-card')) {
        var card = target.closest('.tclas-lineage-card');
        if (card) {
          card.style.opacity = '0';
          card.style.transition = 'opacity .2s';
          setTimeout(() => card.remove(), 220);
        }
        return;
      }

      // ── Remove a single surname row ─────────────────────────────────
      if (target.closest('.tclas-repeater-remove')) {
        var row = target.closest('.tclas-repeater-row');
        if (row) {
          row.style.opacity = '0';
          row.style.transition = 'opacity .2s';
          setTimeout(() => row.remove(), 220);
        }
        return;
      }

      // ── Add surname to a card ───────────────────────────────────────
      if (target.closest('.tclas-lineage-add-surname')) {
        var card = target.closest('.tclas-lineage-card');
        if (!card) return;
        var idx      = card.getAttribute('data-card-index');
        var surnames = card.querySelector('.tclas-lineage-card__surnames');
        if (!surnames) return;

        var row = document.createElement('div');
        row.className = 'tclas-repeater-row';

        var input = document.createElement('input');
        input.type         = 'text';
        input.name         = 'tclas_lineage_surnames[' + idx + '][]';
        input.className    = 'tclas-story-input';
        input.placeholder  = 'e.g. Wagner';
        input.autocomplete = 'off';
        input.setAttribute('aria-label', 'Paired surname');

        var removeBtn = document.createElement('button');
        removeBtn.type      = 'button';
        removeBtn.className = 'tclas-repeater-remove';
        removeBtn.setAttribute('aria-label', 'Remove');
        removeBtn.textContent = '\u00d7';

        row.appendChild(input);
        row.appendChild(removeBtn);
        surnames.appendChild(row);
        input.focus();
      }
    });
  }

  // ── Travel Log repeater ───────────────────────────────────────────────────
  function initTripRepeater() {
    const addBtn = qs('#tclas-trip-add');
    const list   = qs('#tclas-trips-list');
    if (!addBtn || !list) return;

    const purposeOptions = [
      { value: '',         label: '— select —'           },
      { value: 'heritage', label: 'Heritage research'    },
      { value: 'family',   label: 'Family visit'         },
      { value: 'tourism',  label: 'Tourism / vacation'   },
      { value: 'tclas',    label: 'TCLAS / society event' },
      { value: 'business', label: 'Business'             },
      { value: 'other',    label: 'Other'                },
    ];

    // Build a new trip card element
    function buildCard() {
      const item = document.createElement('div');
      item.className = 'tclas-trip-item';

      // ── top two-column grid
      const fields = document.createElement('div');
      fields.className = 'tclas-trip-fields';

      // Month/Year
      const monthGroup = document.createElement('div');
      monthGroup.className = 'tclas-trip-field-group';
      const monthLabel = document.createElement('label');
      monthLabel.className = 'tclas-trip-label';
      monthLabel.textContent = 'Month & Year';
      const monthInput = document.createElement('input');
      monthInput.type      = 'month';
      monthInput.name      = 'tclas_trip_month_year[]';
      monthInput.className = 'tclas-story-input tclas-trip-month';
      monthInput.min       = '1900-01';
      monthInput.max       = new Date().toISOString().slice(0, 7); // YYYY-MM
      monthInput.setAttribute('aria-label', 'Month & Year');
      monthGroup.appendChild(monthLabel);
      monthGroup.appendChild(monthInput);

      // Purpose
      const purposeGroup = document.createElement('div');
      purposeGroup.className = 'tclas-trip-field-group';
      const purposeLabel = document.createElement('label');
      purposeLabel.className = 'tclas-trip-label';
      purposeLabel.textContent = 'Purpose';
      const purposeSelect = document.createElement('select');
      purposeSelect.name      = 'tclas_trip_purpose[]';
      purposeSelect.className = 'tclas-story-input tclas-trip-purpose';
      purposeSelect.setAttribute('aria-label', 'Purpose');
      purposeOptions.forEach(opt => {
        const o = document.createElement('option');
        o.value       = opt.value;
        o.textContent = opt.label;
        purposeSelect.appendChild(o);
      });
      purposeGroup.appendChild(purposeLabel);
      purposeGroup.appendChild(purposeSelect);

      fields.appendChild(monthGroup);
      fields.appendChild(purposeGroup);
      item.appendChild(fields);

      // Notes — full width
      const notesGroup = document.createElement('div');
      notesGroup.className = 'tclas-trip-field-group tclas-trip-field-group--full';
      const notesLabel = document.createElement('label');
      notesLabel.className = 'tclas-trip-label';
      notesLabel.textContent = 'Notes / highlights';
      const notesArea = document.createElement('textarea');
      notesArea.name        = 'tclas_trip_notes[]';
      notesArea.className   = 'tclas-story-input tclas-trip-notes';
      notesArea.rows        = 2;
      notesArea.placeholder = 'Villages visited, archives searched, relatives met\u2026';
      notesArea.setAttribute('aria-label', 'Notes / highlights');
      notesGroup.appendChild(notesLabel);
      notesGroup.appendChild(notesArea);
      item.appendChild(notesGroup);

      // Remove button
      const removeBtn = document.createElement('button');
      removeBtn.type      = 'button';
      removeBtn.className = 'tclas-trip-remove tclas-repeater-remove';
      removeBtn.setAttribute('aria-label', 'Remove this trip');
      removeBtn.textContent = '\u00d7';
      item.appendChild(removeBtn);

      return { item, monthInput };
    }

    // Add a new card on button click
    addBtn.addEventListener('click', () => {
      const { item, monthInput } = buildCard();
      list.appendChild(item);
      monthInput.focus();
    });

    // Delegated remove handler
    list.addEventListener('click', (e) => {
      const btn = e.target.closest('.tclas-trip-remove');
      if (!btn) return;
      const card = btn.closest('.tclas-trip-item');
      if (!card) return;
      card.style.transition = 'opacity .2s';
      card.style.opacity    = '0';
      setTimeout(() => card.remove(), 220);
    });
  }

  // ── Init ───────────────────────────────────────────────────────────────────
  ready(() => {
    initMobileNav();
    initNewsletterNav();
    initMemberNav();
    initNlSubnav();
    initHubSidebar();
    initRenewBanner();
    initReferralCopy();
    initScrollReveal();
    initNationalDaySeason();
    initDirectoryFilters();
    initDropdowns();
    initSmoothScroll();
    initLtzTooltips();
    initConnectionsPanel();
    initMyStoryForm();
    initTripRepeater();
    initBioCounter();
  });

  // ── Bio character counter ─────────────────────────────────────────────────
  function initBioCounter() {
    const textarea = qs('#tclas-bio-field');
    const counter  = qs('#tclas-bio-chars');
    if ( !textarea || !counter ) return;
    textarea.addEventListener('input', function () {
      counter.textContent = textarea.value.length;
    });
  }

})();
