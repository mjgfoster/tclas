/* Luxembourg Citizenship Quiz — v1.4
 * Dynamic generation-tracing engine and decision logic.
 * Privacy update: year inputs replaced with a single "Born before 1969?" checkbox.
 * No personally identifiable dates are collected or stored.
 */
(function () {
  'use strict';

  // ── State Management ─────────────────────────────────────────────────────
  let state = {
    userBornBefore1969: null,  // boolean — did the applicant themselves exist before 1969?
    isAdopted:          null,
    lineage:            [],    // { relation, bornBefore1969, gender, country }
    currentGenIndex:    0
  };

  const genLabels = [
    'parent',
    'grandparent',
    'great-grandparent',
    'great-great-grandparent',
    'great-great-great-grandparent'
  ];

  // ── Engine Setup ─────────────────────────────────────────────────────────
  const container   = document.getElementById('lcq-quiz-container');
  const questionEl  = document.getElementById('lcq-question-text');
  const buttonsEl   = document.getElementById('lcq-button-container');
  const progressBar = document.getElementById('lcq-progress-bar');
  let history = [];

  if (!container) return;

  // ── Step Definitions ─────────────────────────────────────────────────────
  const steps = {

    start: {
      progress: 10,
      render: () => {
        questionEl.textContent = 'Let\u2019s see if you might qualify for Luxembourgish citizenship by descent.';
        buttonsEl.innerHTML = `
          <div class="cq_form_group">
            <label class="cq_checkbox_label">
              <input type="checkbox" id="user-before-1969" class="cq_checkbox">
              I was born before 1969
            </label>
          </div>
          <button class="cq_option_btn cq_submit_btn mt-2">Continue</button>
        `;
        buttonsEl.querySelector('.cq_submit_btn').addEventListener('click', () => {
          state.userBornBefore1969 = document.getElementById('user-before-1969').checked;
          history.push('start');
          renderStep('adopted_check');
        });
      }
    },

    adopted_check: {
      progress: 20,
      render: () => {
        questionEl.textContent = 'Were you legally adopted as a child?';
        buttonsEl.innerHTML = '';
        createOptionBtn('Yes', () => {
          state.isAdopted = true;
          history.push('adopted_check');
          renderStep('outcome_adopted');
        });
        createOptionBtn('No', () => {
          state.isAdopted = false;
          history.push('adopted_check');
          renderStep('generation_loop');
        });
        buttonsEl.appendChild(makeBackButton());
      }
    },

    generation_loop: {
      progress: 45,
      render: () => {
        // Graceful ceiling: more than 5 generations is outside standard eligibility
        if (state.currentGenIndex >= genLabels.length) {
          renderStep('outcome_too_deep');
          return;
        }

        const rel        = genLabels[state.currentGenIndex];
        const isFirstGen = state.currentGenIndex === 0;
        questionEl.textContent =
          `Let\u2019s trace your ancestry back to Luxembourg. Tell us about your ${rel} (the one in your Luxembourgish bloodline).`;

        buttonsEl.innerHTML = `
          <div class="cq_form_group">
            <button
              type="button"
              id="gen-before1969-toggle"
              class="cq_toggle"
              role="switch"
              aria-checked="${isFirstGen ? 'true' : 'false'}"
              data-label-on="Born before 1969"
              data-label-off="Born 1969 or later"
            >
              <span class="cq_toggle__track"><span class="cq_toggle__thumb"></span></span>
              <span class="cq_toggle__label">${isFirstGen ? 'Born before 1969' : 'Born 1969 or later'}</span>
            </button>
            <p class="cq_label_hint">1969 is when Luxembourg law first allowed women to pass nationality to their children equally.</p>
          </div>
          <div class="cq_form_group">
            <label>Gender</label>
            <select id="gen-gender" class="cq_select_field">
              <option value="male">Male</option>
              <option value="female">Female</option>
            </select>
          </div>
          <div class="cq_form_group">
            <label>Birth Country</label>
            <select id="gen-country" class="cq_select_field">
              <option value="other">United States / Other</option>
              <option value="luxembourg">Luxembourg</option>
            </select>
          </div>
          <button class="cq_option_btn cq_submit_btn mt-2">Continue</button>
        `;
        buttonsEl.appendChild(makeBackButton());

        // Toggle click: flip aria-checked and update label text
        const toggle = document.getElementById('gen-before1969-toggle');
        toggle.addEventListener('click', function () {
          const checked = this.getAttribute('aria-checked') === 'true';
          this.setAttribute('aria-checked', String(!checked));
          this.querySelector('.cq_toggle__label').textContent =
            !checked ? this.dataset.labelOn : this.dataset.labelOff;
        });

        buttonsEl.querySelector('.cq_submit_btn').addEventListener('click', () => {
          const bornBefore1969 = document.getElementById('gen-before1969-toggle').getAttribute('aria-checked') === 'true';
          const gender         = document.getElementById('gen-gender').value;
          const country        = document.getElementById('gen-country').value;

          state.lineage[state.currentGenIndex] = {
            relation: rel,
            bornBefore1969,
            gender,
            country
          };
          history.push('generation_loop_' + state.currentGenIndex);

          if (country === 'luxembourg') {
            evaluateEligibility();
          } else {
            state.currentGenIndex++;
            renderStep('generation_loop');
          }
        });
      }
    },

    living_check: {
      progress: 85,
      render: () => {
        questionEl.textContent =
          'Is the parent or grandparent who passes this Luxembourgish lineage to you currently living?';
        buttonsEl.innerHTML = '';
        createOptionBtn('Yes, they are living', () => renderStep('outcome_article23_living'));
        createOptionBtn('No, they have passed',  () => renderStep('outcome_article23_deceased'));
        buttonsEl.appendChild(makeBackButton());
      }
    },

    // ── Outcomes ─────────────────────────────────────────────────────────────

    outcome_adopted: {
      progress: 100,
      render: () => renderOutcomeScreen(
        'Your situation has some unique nuances.',
        'Because you were legally adopted as a child, the standard rules for tracing citizenship by descent apply differently depending on the circumstances and timing of your adoption. You may still qualify for Luxembourgish citizenship, but it requires a careful, case-by-case evaluation that a quiz cannot reliably provide. We strongly recommend contacting the Luxembourg Ministry of Justice directly to assess your specific situation.',
        null
      )
    },

    outcome_article7: {
      progress: 100,
      render: () => renderOutcomeScreen(
        'It looks like you may qualify through Article 7 (Direct Descent).',
        'Based on your answers, your Luxembourgish bloodline appears to have passed unbroken from generation to generation. Under Article 7 of the Luxembourg Nationality Act, you likely already hold citizenship by birthright\u2014you simply need to formally claim and register it. The process is handled entirely by mail; there is no language test, no travel to Luxembourg, and no residency requirement. Your qualifying ancestor must have been born between 1815 and 1946 within the borders of modern-day Luxembourg (not the former Belgian Luxembourg province).',
        'article7'
      )
    },

    outcome_article23_living: {
      progress: 100,
      render: () => renderOutcomeScreen(
        'It looks like you may qualify through Article 23.',
        'Because a female ancestor in your line passed citizenship to a child born before 1969, the direct Article 7 line was technically broken under the law of that era. Article 23 exists specifically to address this situation. However, it\u2019s a two-step process: your living parent or grandparent must first be formally recognized as a Luxembourg citizen through their own Article 7 application. Once they receive recognition, you can then apply for nationality through Article 23. Note: Article 23 extends only one generation\u2014the connecting relative must be your parent or grandparent, not a great-grandparent. The Article 23 process requires an in-person appointment at the Luxembourg Ministry of Justice in Luxembourg City, with roughly a four-month waiting period.',
        'article23'
      )
    },

    outcome_article23_deceased: {
      progress: 100,
      render: () => renderOutcomeScreen(
        'It looks like you may qualify through the Article 7 + Article 23 (Posthumous) pathway.',
        'Because a female ancestor in your line passed citizenship to a child born before 1969, the direct Article 7 line was technically broken. However, a two-phase process may still be available. In Phase 1, you petition for posthumous recognition of your late parent or grandparent as someone who would have qualified for Luxembourg nationality under Article 7. If granted, their citizenship is recognized retroactively. In Phase 2, you then apply for nationality yourself under Article 23. This pathway is more involved, but it has been successfully completed by other Americans navigating the same situation. An in-person appointment in Luxembourg City will be required.',
        'article23'
      )
    },

    outcome_too_deep: {
      progress: 100,
      render: () => renderOutcomeScreen(
        'Your Luxembourgish connection appears to go back many generations.',
        'This quiz traces ancestry up to five generations (great-great-great-grandparent). Your Luxembourg connection appears to be six or more generations removed, which is outside the scope of the standard Article 7 pathway and beyond the reach of Article 23. While this makes qualifying by descent unlikely under current law, every family\u2019s records are different, and there may be details in your specific lineage that change the picture. We encourage you to consult directly with the Luxembourg Ministry of Justice or a citizenship specialist.',
        null
      )
    }

  };

  // ── Evaluation Logic ─────────────────────────────────────────────────────
  function evaluateEligibility() {
    let lineBroken = false;

    // Build chronological chain: [Lux Ancestor \u2192 \u2026 \u2192 Parent \u2192 You]
    const chrono = [...state.lineage].reverse();
    chrono.push({ relation: 'You', bornBefore1969: state.userBornBefore1969, gender: 'N/A' });

    for (let i = 0; i < chrono.length - 1; i++) {
      const current = chrono[i];
      const child   = chrono[i + 1];

      // The 1969 Rule: a female ancestor whose child was born before 1969
      // could not transmit citizenship under the law at that time.
      if (current.gender === 'female' && child.bornBefore1969) {
        lineBroken = true;
      }
    }

    if (lineBroken) {
      renderStep('living_check');
    } else {
      renderStep('outcome_article7');
    }
  }

  // ── Render Helpers ───────────────────────────────────────────────────────
  function renderStep(stepKey) {
    const step = steps[stepKey];
    if (!step) return;
    progressBar.style.width = step.progress + '%';
    progressBar.setAttribute('aria-valuenow', step.progress);
    step.render();
  }

  function createOptionBtn(label, onClick) {
    const btn = document.createElement('button');
    btn.className   = 'cq_option_btn';
    btn.textContent = label;
    btn.addEventListener('click', onClick);
    buttonsEl.appendChild(btn);
  }

  function makeBackButton() {
    const btn = document.createElement('button');
    btn.className   = 'cq_back_btn mt-4';
    btn.textContent = '\u2190 Back';
    btn.addEventListener('click', () => {
      let lastStep = history.pop();

      // Restore generation index if backing out of a generation step
      if (lastStep && lastStep.startsWith('generation_loop_')) {
        state.currentGenIndex = parseInt(lastStep.split('_')[2], 10);
        lastStep = 'generation_loop';
      }

      // If history is now empty, reset all state and return to start
      if (!lastStep) {
        state.currentGenIndex    = 0;
        state.lineage            = [];
        state.userBornBefore1969 = null;
        state.isAdopted          = null;
        lastStep = 'start';
      }

      renderStep(lastStep);
    });
    return btn;
  }

  function renderOutcomeScreen(headline, bodyText, outcomeType) {
    questionEl.textContent = headline;
    buttonsEl.innerHTML    = '';

    // ── Body text ──────────────────────────────────────────────────────────
    const bodyEl = document.createElement('p');
    bodyEl.className   = 'cq_outcome_body';
    bodyEl.textContent = bodyText;
    buttonsEl.appendChild(bodyEl);

    // ── Soft member CTA (qualifying outcomes only) ─────────────────────────
    if (outcomeType === 'article7' || outcomeType === 'article23') {
      buttonsEl.insertAdjacentHTML('beforeend', `
        <div class="cq_cta_box">
          <p class="cq_cta_text"><strong>Want the full picture?</strong> TCLAS members have access to a detailed, step-by-step guide that walks through the complete application process for both Article 7 and Article 23. We also host occasional peer-to-peer citizenship workshops where members share firsthand experiences navigating the process.</p>
          <a href="/membership/" class="cq_option_btn cq_submit_btn cq_cta_btn">Learn about TCLAS membership \u2192</a>
        </div>
      `);
    }

    // ── Legal disclaimer ───────────────────────────────────────────────────
    buttonsEl.insertAdjacentHTML('beforeend', `
      <div class="cq_disclaimer">
        <strong>Important:</strong> This quiz is strictly informational and does not constitute legal advice. It is not written or reviewed by an attorney. Luxembourg citizenship law is complex and individual circumstances vary\u2014naturalization history, adoption, border changes, and other factors can all affect eligibility. For a conclusive determination, please contact the <a href="https://mj.gouvernement.lu/en/particuliers/nationalite.html" target="_blank" rel="noopener noreferrer">Luxembourg Ministry of Justice</a> directly.
      </div>
    `);

    // ── Email results section ──────────────────────────────────────────────
    buttonsEl.insertAdjacentHTML('beforeend', `
      <div class="cq_email_section">
        <p class="cq_email_label">Send yourself a copy of these results:</p>
        <div class="cq_form_group">
          <input type="email" id="lcq-email" class="cq_input_field" placeholder="your@email.com" autocomplete="email">
        </div>
        <button id="lcq-email-btn" class="cq_option_btn cq_submit_btn">Send Results</button>
        <p id="lcq-email-status" class="cq_email_status" aria-live="polite" role="status"></p>
      </div>
    `);

    document.getElementById('lcq-email-btn').addEventListener('click', () => {
      const emailInput = document.getElementById('lcq-email');
      const statusEl   = document.getElementById('lcq-email-status');
      const email      = emailInput ? emailInput.value.trim() : '';

      if (!email) {
        statusEl.textContent = 'Please enter your email address.';
        return;
      }

      statusEl.textContent = 'Sending\u2026';

      const payload = new FormData();
      payload.append('action',      'lcq_send_results');
      payload.append('nonce',       (typeof lcqData !== 'undefined') ? lcqData.nonce : '');
      payload.append('email',       email);
      payload.append('result_text', headline + '\n\n' + bodyText);

      const ajaxUrl = (typeof lcqData !== 'undefined')
        ? lcqData.ajax_url
        : '/wp-admin/admin-ajax.php';

      fetch(ajaxUrl, { method: 'POST', body: payload })
        .then(r   => r.json())
        .then(res => {
          statusEl.textContent = res.success
            ? 'Results sent! Check your inbox.'
            : 'Something went wrong\u2014please try again.';
        })
        .catch(() => {
          statusEl.textContent = 'Something went wrong\u2014please try again.';
        });
    });

    // ── Restart button ─────────────────────────────────────────────────────
    const restartBtn = document.createElement('button');
    restartBtn.className   = 'cq_restart_btn mt-4';
    restartBtn.textContent = 'Start over';
    restartBtn.addEventListener('click', () => {
      history = [];
      state   = { userBornBefore1969: null, isAdopted: null, lineage: [], currentGenIndex: 0 };
      renderStep('start');
    });
    buttonsEl.appendChild(restartBtn);
  }

  // Start the engine
  renderStep('start');

})();
