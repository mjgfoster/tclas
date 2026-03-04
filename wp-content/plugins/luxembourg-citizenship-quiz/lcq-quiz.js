/* Luxembourg Citizenship Quiz — v1.5
 * Dynamic generation-tracing engine and decision logic.
 * v1.5: Adds Belgium/borders clarifier question + June 9, 1815 date gate before evaluation.
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
            renderStep('ancestor_borders');
          } else {
            state.currentGenIndex++;
            renderStep('generation_loop');
          }
        });
      }
    },

    // ── Territory and date gate (inserted before evaluation) ─────────────────

    ancestor_borders: {
      progress: 70,
      render: () => {
        const rel = genLabels[state.currentGenIndex];
        questionEl.textContent =
          'Was your ' + rel + ' born in a town that is currently inside the Grand Duchy of Luxembourg?';
        buttonsEl.innerHTML = '';

        const noteEl = document.createElement('p');
        noteEl.className = 'cq_label_hint';
        noteEl.textContent =
          'Note: The historical Province of Luxembourg \u2014 now in southeastern Belgium \u2014 is often confused with the modern Grand Duchy. They are different countries today.';
        buttonsEl.appendChild(noteEl);

        createOptionBtn('Yes \u2014 the Grand Duchy of Luxembourg', () => {
          history.push('ancestor_borders');
          renderStep('ancestor_date');
        });
        createOptionBtn('No \u2014 or possibly the Belgian Luxembourg province', () => {
          renderStep('outcome_wrong_territory');
        });
        createOptionBtn('I\u2019m not sure', () => {
          renderStep('outcome_unsure_territory');
        });
        buttonsEl.appendChild(makeBackButton());
      }
    },

    ancestor_date: {
      progress: 82,
      render: () => {
        const rel = genLabels[state.currentGenIndex];
        questionEl.textContent =
          'Was your ' + rel + ' born on or after June 9, 1815?';
        buttonsEl.innerHTML = '';

        const noteEl = document.createElement('p');
        noteEl.className = 'cq_label_hint';
        noteEl.textContent =
          'June 9, 1815 is when the Congress of Vienna established Luxembourg as an independent Grand Duchy \u2014 the date from which its nationality laws apply.';
        buttonsEl.appendChild(noteEl);

        createOptionBtn('Yes \u2014 born 1815 or later', () => {
          history.push('ancestor_date');
          evaluateEligibility();
        });
        createOptionBtn('No \u2014 born before June 9, 1815', () => {
          renderStep('outcome_pre_1815');
        });
        createOptionBtn('I\u2019m not sure', () => {
          renderStep('outcome_unsure_date');
        });
        buttonsEl.appendChild(makeBackButton());
      }
    },

    living_check: {
      progress: 90,
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
    },

    // ── Territory and date gate outcomes ──────────────────────────────────────

    outcome_wrong_territory: {
      progress: 100,
      render: () => renderOutcomeScreen(
        'Your ancestor may have been born in Belgium, not Luxembourg.',
        'The Province of Luxembourg \u2014 now part of southeastern Belgium \u2014 was historically part of the same region as the Grand Duchy until 1839, when the Treaty of London divided the territory. Modern Luxembourg citizenship law applies only to citizens of the Grand Duchy. If your ancestor was born in a town now in Belgium (such as Arlon, Bastogne, or Libramont), the Article 7 descent pathway does not apply to that line. We encourage you to double-check your ancestor\u2019s birthplace against a current map of the Grand Duchy of Luxembourg, then contact the Luxembourg Ministry of Justice if you have questions about your specific situation.',
        null
      )
    },

    outcome_pre_1815: {
      progress: 100,
      render: () => renderOutcomeScreen(
        'Your ancestor predates Luxembourg\u2019s founding.',
        'Luxembourg was established as an independent Grand Duchy on June 9, 1815, by the Congress of Vienna. Citizenship by descent under Articles 7 and 23 applies to descendants of citizens of the Grand Duchy from that date forward. An ancestor born before 1815 would not have been a citizen of the modern Luxembourg state as defined by current nationality law. While your family\u2019s historical connection to the region is genuine, the formal citizenship recovery pathway is unlikely to apply to this line. We encourage you to contact the Luxembourg Ministry of Justice directly if you believe your circumstances may be an exception.',
        null
      )
    },

    outcome_unsure_territory: {
      progress: 100,
      render: () => renderOutcomeScreen(
        'Worth confirming before you go further.',
        'The historical Province of Luxembourg and the modern Grand Duchy share a name and much of their history \u2014 but they are different countries today. The 1839 Treaty of London split the original duchy: the western, French-speaking portion became a Belgian province, while the eastern portion remained the independent Grand Duchy. If your ancestor\u2019s records show \u201cLuxembourg\u201d as a birthplace, cross-reference the specific commune against a current map of the Grand Duchy. The Luxembourg National Archives (ANLux) and genealogical databases such as Portail G\u00e9n\u00e9alogique Grand-Ducal can help confirm whether a town is inside modern Luxembourg. Once verified, you can retake this quiz with that detail confirmed.',
        null
      )
    },

    outcome_unsure_date: {
      progress: 100,
      render: () => renderOutcomeScreen(
        'You\u2019ll want to confirm the birth year before applying.',
        'The June 9, 1815 threshold matters for your application \u2014 that is when Luxembourg\u2019s nationality laws began. If you don\u2019t have a confirmed birth year for your Luxembourg ancestor, vital records offices, the Luxembourg National Archives (ANLux), or genealogical databases can help locate birth records. Parish registers (registres paroissiaux) and civil registration records (registres d\u2019\u00e9tat civil) from Luxembourg communes are increasingly digitized and searchable online. Once you have a confirmed birth year \u2014 even an approximate decade from a death certificate \u2014 you can retake this quiz or proceed to the consulate directly if the date is clearly after 1815.',
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
          <a href="/join/" class="cq_option_btn cq_submit_btn cq_cta_btn">Learn about TCLAS membership \u2192</a>
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
