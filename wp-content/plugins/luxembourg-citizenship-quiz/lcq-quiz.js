/* Luxembourg Citizenship Quiz — v2.0
 * Complete rewrite: qualifying gates → lineage tracing → evaluation.
 * Gender inferred from relationship label (grandmother/grandfather).
 * No toggles, no dropdowns — clean Yes/No option buttons throughout.
 * Color-coded: green (yes), red (no), gray (neutral).
 * Born-before-1969 auto-skips for further-back generations.
 */
(function () {
	'use strict';

	// ── DOM References ─────────────────────────────────────────────────────
	const container     = document.getElementById('lcq-quiz-container');
	const questionEl    = document.getElementById('lcq-question-text');
	const buttonsEl     = document.getElementById('lcq-button-container');
	const progressBar   = document.getElementById('lcq-progress-bar');
	const layoutEl      = document.getElementById('lcq-quiz-layout');
	const sidebarEl     = document.getElementById('lcq-lineage-sidebar');
	const lineageListEl = document.getElementById('lcq-lineage-list');

	if (!container) return;

	// ── State ──────────────────────────────────────────────────────────────
	const freshState = () => ({
		// Step 1 — Qualifying gates
		hasAncestor:        null,   // 'yes'|'no'|'unsure'
		modernBorders:      null,   // 'yes'|'no'|'unsure'
		after1815:          null,   // 'yes'|'no'|'unsure'
		// Step 2 — Lineage
		userBornBefore1969: null,   // boolean
		isAdopted:          null,   // boolean
		chosenSide:         null,   // 'mom'|'dad'
		lineage:            [],     // { label, gender ('f'|'m'), bornBefore1969, bornInLux }
		genIndex:           0       // 0 = parent done; 1+ = grandparent onward
	});

	let state   = freshState();
	let history = [];

	// ── Generation Labels ────────────────────────────────────────────────
	// index 0: mom / dad
	// index 1: grandmother / grandfather
	// index 2+: great-grandmother / great-grandfather (with "great-" repeated)
	function genLabel(index, gender) {
		if (index === 0) return gender === 'f' ? 'mom' : 'dad';
		const base = gender === 'f' ? 'grandmother' : 'grandfather';
		if (index === 1) return base;
		return 'great-'.repeat(index - 1) + base;
	}

	function prevLabel() {
		return state.lineage[state.genIndex - 1].label;
	}

	// ── Progress ─────────────────────────────────────────────────────────
	function setProgress(pct) {
		progressBar.style.width = pct + '%';
		progressBar.setAttribute('aria-valuenow', pct);
	}

	// ── History (snapshot-based for clean back navigation) ────────────────
	function pushHistory(stepKey) {
		history.push({
			step:  stepKey,
			state: JSON.parse(JSON.stringify(state))
		});
	}

	function goBack() {
		const prev = history.pop();
		if (!prev) {
			state = freshState();
			hideSidebar();
			renderStep('gate_ancestor');
			return;
		}
		state = prev.state;
		if (state.lineage.length === 0) hideSidebar();
		renderStep(prev.step);
	}

	// ── Render Helpers ───────────────────────────────────────────────────
	function renderStep(key) {
		const step = steps[key];
		if (!step) return;
		if (step.progress != null) setProgress(step.progress);
		step.render();

		// Update lineage sidebar when lineage data exists
		if (state.lineage.length > 0) {
			showSidebar();
			renderLineage();
		}
	}

	// variant: 'yes' | 'no' | 'idk' | 'neutral' | null
	function addOptionBtn(label, onClick, variant) {
		const btn = document.createElement('button');
		btn.className = 'cq_option_btn';
		// Auto-tag Yes/No/IDK from the label so the theme can style them distinctly.
		if (!variant) {
			if (label === 'Yes') variant = 'yes';
			else if (label === 'No') variant = 'no';
			else if (label === 'I don’t know') variant = 'idk';
		}
		if (variant) btn.classList.add('cq_option_btn--' + variant);
		// Bilingual Yes/No: English on top, Luxembourgish as a flourish below.
		// aria-hidden on the Lux span so screen readers announce just the English label.
		if (variant === 'yes' || variant === 'no') {
			const en = document.createElement('span');
			en.className = 'cq_option_btn__en';
			en.textContent = label;
			btn.appendChild(en);
			const lux = document.createElement('span');
			lux.className = 'cq_option_btn__lux';
			lux.setAttribute('aria-hidden', 'true');
			lux.textContent = (variant === 'yes') ? 'Jo' : 'Neen';
			btn.appendChild(lux);
		} else {
			btn.textContent = label;
		}
		btn.addEventListener('click', onClick);
		buttonsEl.appendChild(btn);
		return btn;
	}

	function addHint(text) {
		const p = document.createElement('p');
		p.className   = 'cq_label_hint';
		// Hints contain hand-authored <strong>/<em> tags \u2014 render them as HTML.
		p.innerHTML = text;
		buttonsEl.appendChild(p);
	}

	function addBackButton() {
		const btn = document.createElement('button');
		btn.className   = 'cq_back_btn';
		btn.textContent = '\u2190 Back';
		btn.addEventListener('click', goBack);
		// Attach to the quiz container (sibling of buttonsEl) so CSS can park it
		// in a dedicated lower-left slot rather than wherever the answer buttons end.
		const container = document.getElementById('lcq-quiz-container');
		(container || buttonsEl).appendChild(btn);
	}

	// ── Lineage sidebar ─────────────────────────────────────────────────

	function showSidebar() {
		if (!sidebarEl) return;
		sidebarEl.removeAttribute('hidden');
		if (layoutEl) layoutEl.classList.add('cq_layout--with-sidebar');
	}

	function hideSidebar() {
		if (!sidebarEl) return;
		sidebarEl.setAttribute('hidden', '');
		if (layoutEl) layoutEl.classList.remove('cq_layout--with-sidebar');
	}

	function buildNode(label, tags, nodeState) {
		var li = document.createElement('li');
		li.className = 'cq_lineage_node';
		if (nodeState) li.classList.add('cq_lineage_node--' + nodeState);

		var dot = document.createElement('span');
		dot.className = 'cq_lineage_node__dot';
		dot.setAttribute('aria-hidden', 'true');
		li.appendChild(dot);

		var info = document.createElement('div');
		info.className = 'cq_lineage_node__info';

		var labelEl = document.createElement('span');
		labelEl.className = 'cq_lineage_node__label';
		labelEl.textContent = label;
		info.appendChild(labelEl);

		var filtered = tags.filter(function (t) { return t !== null; });
		if (filtered.length > 0) {
			var tagsEl = document.createElement('div');
			tagsEl.className = 'cq_lineage_node__tags';
			filtered.forEach(function (text) {
				var tag = document.createElement('span');
				tag.className = 'cq_lineage_tag';
				if (text === 'Born in Luxembourg') tag.classList.add('cq_lineage_tag--lux');
				if (text === 'Born before 1969')   tag.classList.add('cq_lineage_tag--pre69');
				tag.textContent = text;
				tagsEl.appendChild(tag);
			});
			info.appendChild(tagsEl);
		}

		li.appendChild(info);
		return li;
	}

	function renderLineage() {
		if (!lineageListEl) return;
		lineageListEl.innerHTML = '';

		// "You" node — always first
		lineageListEl.appendChild(buildNode('You', [
			state.userBornBefore1969 === true  ? 'Born before 1969' : null,
			state.userBornBefore1969 === false ? 'Born after 1969'  : null
		], 'done'));

		// Each lineage person
		for (var i = 0; i < state.lineage.length; i++) {
			var person = state.lineage[i];
			if (!person) continue;

			var tags = [];
			if (person.bornBefore1969 === true)  tags.push('Born before 1969');
			if (person.bornBefore1969 === false) tags.push('Born after 1969');
			if (person.bornInLux === true)        tags.push('Born in Luxembourg');

			var ns = 'done';
			if (person.bornInLux === true) {
				ns = 'lux';
			} else if (i === state.genIndex && person.bornInLux === null) {
				ns = 'active';
			}

			lineageListEl.appendChild(buildNode(person.label, tags, ns));
		}
	}

	function renderInlineLineage() {
		var wrapper = document.createElement('div');
		wrapper.className = 'cq_lineage_inline';

		var title = document.createElement('h4');
		title.className = 'cq_lineage_inline__title';
		title.textContent = 'What you entered';
		wrapper.appendChild(title);

		var list = document.createElement('ol');
		list.className = 'cq_lineage_list';

		list.appendChild(buildNode('You', [
			state.userBornBefore1969 === true  ? 'Born before 1969' : null,
			state.userBornBefore1969 === false ? 'Born after 1969'  : null
		], 'done'));

		for (var i = 0; i <= state.genIndex; i++) {
			var person = state.lineage[i];
			if (!person) continue;
			var tags = [];
			if (person.bornBefore1969 === true)  tags.push('Born before 1969');
			if (person.bornBefore1969 === false) tags.push('Born after 1969');
			if (person.bornInLux === true)        tags.push('Born in Luxembourg');
			var ns = person.bornInLux === true ? 'lux' : 'done';
			list.appendChild(buildNode(person.label, tags, ns));
		}

		wrapper.appendChild(list);
		buttonsEl.insertBefore(wrapper, buttonsEl.firstChild);
	}

	// Structured lineage for the results email. Deliberately carries no prose:
	// the server derives every relationship label itself (see lcq_gen_label in
	// the plugin PHP), so the email body is never assembled from what the
	// browser says. Entries stay contiguous from index 0 because the server
	// reads position as generation depth — a gap would mislabel everyone after it.
	function buildLineagePayload() {
		var payload = [];
		for (var i = 0; i <= state.genIndex; i++) {
			var p = state.lineage[i];
			if (!p) break;
			payload.push({
				g:   p.gender === 'f' ? 'f' : 'm',
				b69: p.bornBefore1969,
				lux: p.bornInLux === true
			});
		}
		return payload;
	}

	// ── 1969 skip helper ─────────────────────────────────────────────────
	// Once anyone in the chain was born before 1969, everyone further back
	// was too (they're older). Check the immediately prior person.
	function priorBornBefore1969() {
		if (state.genIndex === 0) return state.userBornBefore1969;
		return state.lineage[state.genIndex - 1].bornBefore1969;
	}

	// After choosing side, decide whether to ask parent_born or skip it
	function afterSideChosen() {
		if (state.userBornBefore1969) {
			// Parent is older → definitely born before 1969
			state.lineage[0].bornBefore1969 = true;
			state.genIndex = 1;
			renderStep('gen_gender');
		} else {
			renderStep('parent_born');
		}
	}

	// After choosing gender in gen loop, decide whether to ask gen_born
	function afterGenderChosen() {
		if (state.lineage[state.genIndex - 1].bornBefore1969) {
			// Prior person born before 1969 → this ancestor definitely was too
			state.lineage[state.genIndex].bornBefore1969 = true;
			renderStep('gen_country');
		} else {
			renderStep('gen_born');
		}
	}

	// ══════════════════════════════════════════════════════════════════════
	// STEP DEFINITIONS
	// ══════════════════════════════════════════════════════════════════════

	const steps = {

		// ── STEP 1: Qualifying Gates ─────────────────────────────────────

		gate_ancestor: {
			progress: 5,
			render: () => {
				questionEl.innerHTML =
					'Do you have an ancestor born in <strong>Luxembourg</strong> after <strong>June 9, 1815</strong>?';
				buttonsEl.innerHTML = '';

				addOptionBtn('Yes', () => {
					pushHistory('gate_ancestor');
					state.hasAncestor = 'yes';
					state.after1815 = 'yes';
					renderStep('gate_borders');
				}, 'yes');
				addOptionBtn('No', () => {
					state.hasAncestor = 'no';
					renderStep('outcome_ineligible_no_ancestor');
				}, 'no');
				addOptionBtn('I don\u2019t know', () => {
					state.hasAncestor = 'unsure';
					renderStep('outcome_unsure_ancestor');
				}, 'neutral');
			}
		},

		gate_borders: {
			progress: 10,
			render: () => {
				questionEl.innerHTML =
					'Was your ancestor born within the <strong>modern borders of Luxembourg</strong>?';
				buttonsEl.innerHTML = '';

				addHint(
					'The historical Province of Luxembourg \u2014 now in southeastern Belgium \u2014 ' +
					'is often confused with the modern Grand Duchy. They are different countries today.'
				);

				addOptionBtn('Yes', () => {
					pushHistory('gate_borders');
					state.modernBorders = 'yes';
					renderStep('intro');
				}, 'yes');
				addOptionBtn('No', () => {
					state.modernBorders = 'no';
					renderStep('outcome_ineligible_territory');
				}, 'no');
				addOptionBtn('I don\u2019t know', () => {
					state.modernBorders = 'unsure';
					renderStep('outcome_unsure_territory');
				}, 'neutral');

				addBackButton();
			}
		},

		// ── STEP 2: Lineage Tracing ──────────────────────────────────────

		intro: {
			progress: 20,
			render: () => {
				questionEl.textContent =
					'Tell us a little about your direct family line going back to Luxembourg. ' +
					'We\u2019ll start with you!';
				buttonsEl.innerHTML = '';

				const btn = document.createElement('button');
				btn.className   = 'cq_option_btn cq_submit_btn';
				btn.textContent = 'Continue';
				btn.addEventListener('click', () => {
					pushHistory('intro');
					renderStep('user_born');
				});
				buttonsEl.appendChild(btn);

				addBackButton();
			}
		},

		user_born: {
			progress: 25,
			render: () => {
				questionEl.innerHTML = 'When were you born?';
				buttonsEl.innerHTML = '';

				addOptionBtn('Before 1969', () => {
					pushHistory('user_born');
					state.userBornBefore1969 = true;
					renderStep('adopted_check');
				}, 'neutral');
				addOptionBtn('In or after 1969', () => {
					pushHistory('user_born');
					state.userBornBefore1969 = false;
					renderStep('adopted_check');
				}, 'neutral');

				addBackButton();
			}
		},

		adopted_check: {
			progress: 28,
			render: () => {
				questionEl.innerHTML = 'Were you <strong>legally adopted</strong> as a child?';
				buttonsEl.innerHTML = '';

				addOptionBtn('Yes', () => {
					state.isAdopted = true;
					renderStep('outcome_adopted');
				}, 'yes');
				addOptionBtn('No', () => {
					pushHistory('adopted_check');
					state.isAdopted = false;
					renderStep('choose_side');
				}, 'no');

				addBackButton();
			}
		},

		choose_side: {
			progress: 30,
			render: () => {
				questionEl.innerHTML =
					'What <strong>side of your family</strong> is Luxembourgish?';
				buttonsEl.innerHTML = '';

				addHint(
					'Think about which side is most likely to have an <strong>unbroken male line</strong> or a <strong>female ancestor with children born after 1969</strong>. ' +
					'If unsure, pick the side you think is closest in generations to Luxembourg.'
				);

				addOptionBtn('My mom\u2019s side', () => {
					pushHistory('choose_side');
					setSide('mom');
					afterSideChosen();
				}, 'neutral');
				addOptionBtn('My dad\u2019s side', () => {
					pushHistory('choose_side');
					setSide('dad');
					afterSideChosen();
				}, 'neutral');
				addOptionBtn('Both', () => {
					pushHistory('choose_side');
					renderStep('choose_side_both');
				}, 'neutral');

				addBackButton();
			}
		},

		choose_side_both: {
			progress: 30,
			render: () => {
				questionEl.innerHTML =
					'Great \u2014 let\u2019s start with your <strong>father\u2019s side</strong>.';
				buttonsEl.innerHTML = '';

				addHint(
					'When the Luxembourgish line could come from either parent, we trace the father\u2019s side first \u2014 the male-descent route (Article 7) usually has the clearest paperwork trail. You can retake the quiz for your mother\u2019s side later.'
				);

				const btn = document.createElement('button');
				btn.className = 'cq_option_btn cq_submit_btn';
				btn.textContent = 'Continue';
				btn.addEventListener('click', () => {
					pushHistory('choose_side_both');
					setSide('dad');
					afterSideChosen();
				});
				buttonsEl.appendChild(btn);

				addBackButton();
			}
		},

		parent_born: {
			progress: 35,
			render: () => {
				const label = state.lineage[0].label;
				questionEl.innerHTML =
					'When was your <strong>' + label + '</strong> born?';
				buttonsEl.innerHTML = '';

				addOptionBtn('Before 1969', () => {
					pushHistory('parent_born');
					state.lineage[0].bornBefore1969 = true;
					state.genIndex = 1;
					renderStep('gen_gender');
				}, 'neutral');
				addOptionBtn('In or after 1969', () => {
					pushHistory('parent_born');
					state.lineage[0].bornBefore1969 = false;
					state.genIndex = 1;
					renderStep('gen_gender');
				}, 'neutral');

				addBackButton();
			}
		},

		// ── Generation Loop ──────────────────────────────────────────────

		gen_gender: {
			progress: null,
			render: () => {
				if (state.genIndex >= 7) {
					renderStep('outcome_too_deep');
					return;
				}

				setProgress(40 + state.genIndex * 7);

				const prev = prevLabel();
				const femaleLabel = genLabel(state.genIndex, 'f');
				const maleLabel   = genLabel(state.genIndex, 'm');
				questionEl.innerHTML =
					'Is your ' + prev + '\u2019s Luxembourgish parent <strong>your ' + femaleLabel + '</strong> or <strong>your ' + maleLabel + '</strong>?';
				buttonsEl.innerHTML = '';

				addOptionBtn('My ' + femaleLabel, () => {
					pushHistory('gen_gender');
					state.lineage[state.genIndex] = {
						label:          femaleLabel,
						gender:         'f',
						bornBefore1969: null,
						bornInLux:      null
					};
					afterGenderChosen();
				}, 'neutral');
				addOptionBtn('My ' + maleLabel, () => {
					pushHistory('gen_gender');
					state.lineage[state.genIndex] = {
						label:          maleLabel,
						gender:         'm',
						bornBefore1969: null,
						bornInLux:      null
					};
					afterGenderChosen();
				}, 'neutral');
				// If both were Luxembourgish, trace via the male line for the clearest path.
				addOptionBtn('Both', () => {
					pushHistory('gen_gender');
					state.lineage[state.genIndex] = {
						label:          maleLabel,
						gender:         'm',
						bornBefore1969: null,
						bornInLux:      null
					};
					afterGenderChosen();
				}, 'neutral');

				addBackButton();
			}
		},

		gen_born: {
			progress: null,
			render: () => {
				setProgress(43 + state.genIndex * 7);

				var label = state.lineage[state.genIndex].label;
				questionEl.innerHTML =
					'When was your <strong>' + label + '</strong> born?';
				buttonsEl.innerHTML = '';

				addOptionBtn('Before 1969', () => {
					pushHistory('gen_born');
					state.lineage[state.genIndex].bornBefore1969 = true;
					renderStep('gen_country');
				}, 'neutral');
				addOptionBtn('In or after 1969', () => {
					pushHistory('gen_born');
					state.lineage[state.genIndex].bornBefore1969 = false;
					renderStep('gen_country');
				}, 'neutral');

				addBackButton();
			}
		},

		gen_country: {
			progress: null,
			render: () => {
				setProgress(46 + state.genIndex * 7);

				var label = state.lineage[state.genIndex].label;
				questionEl.innerHTML =
					'Was your <strong>' + label + '</strong> born in <strong>Luxembourg</strong>?';
				buttonsEl.innerHTML = '';

				addOptionBtn('Yes', () => {
					pushHistory('gen_country');
					state.lineage[state.genIndex].bornInLux = true;
					evaluateEligibility();
				}, 'yes');
				addOptionBtn('No', () => {
					pushHistory('gen_country');
					state.lineage[state.genIndex].bornInLux = false;
					state.genIndex++;
					renderStep('gen_gender');
				}, 'no');

				addBackButton();
			}
		},

		// ── Living Check ─────────────────────────────────────────────────

		living_check: {
			progress: 90,
			render: () => {
				questionEl.innerHTML =
					'Is the parent or grandparent who passes this Luxembourgish lineage ' +
					'to you <strong>currently living</strong>?';
				buttonsEl.innerHTML = '';

				addOptionBtn('Yes, they are living', () => {
					renderStep('outcome_article23_living');
				}, 'yes');
				addOptionBtn('No, they have passed', () => {
					renderStep('outcome_article23_deceased');
				}, 'no');

				addBackButton();
			}
		},

		// ══════════════════════════════════════════════════════════════════
		// OUTCOMES
		// ══════════════════════════════════════════════════════════════════

		// ── Step 1 gate outcomes ─────────────────────────────────────────

		outcome_ineligible_no_ancestor: {
			progress: 100,
			render: () => renderOutcome('outcome_ineligible_no_ancestor')
		},

		outcome_ineligible_territory: {
			progress: 100,
			render: () => renderOutcome('outcome_ineligible_territory')
		},

		outcome_unsure_ancestor: {
			progress: 100,
			render: () => renderOutcome('outcome_unsure_ancestor')
		},

		outcome_unsure_territory: {
			progress: 100,
			render: () => renderOutcome('outcome_unsure_territory')
		},

		// ── Lineage outcomes ─────────────────────────────────────────────

		outcome_adopted: {
			progress: 100,
			render: () => renderOutcome('outcome_adopted')
		},

		outcome_too_deep: {
			progress: 100,
			render: () => renderOutcome('outcome_too_deep')
		},

		outcome_article7: {
			progress: 100,
			render: () => renderOutcome('outcome_article7')
		},

		outcome_article23_living: {
			progress: 100,
			render: () => renderOutcome('outcome_article23_living')
		},

		outcome_article23_deceased: {
			progress: 100,
			render: () => renderOutcome('outcome_article23_deceased')
		}
	};

	// ── Side helper ──────────────────────────────────────────────────────
	function setSide(side) {
		state.chosenSide = side;
		state.lineage[0] = {
			label:          side === 'mom' ? 'mom' : 'dad',
			gender:         side === 'mom' ? 'f' : 'm',
			bornBefore1969: null,
			bornInLux:      null
		};
	}

	// ── Evaluation Logic ─────────────────────────────────────────────────
	function evaluateEligibility() {
		var lineBroken = false;

		// Build chronological chain: [Lux ancestor → … → parent → you]
		var chain  = state.lineage.slice(0, state.genIndex + 1);
		var chrono = [].concat(chain).reverse();
		chrono.push({
			label:          'you',
			gender:         null,
			bornBefore1969: state.userBornBefore1969
		});

		for (var i = 0; i < chrono.length - 1; i++) {
			var sender   = chrono[i];
			var receiver = chrono[i + 1];

			// The 1969 Rule: a female ancestor whose child was born before 1969
			// could not transmit citizenship under the law at that time.
			if (sender.gender === 'f' && receiver.bornBefore1969) {
				lineBroken = true;
			}
		}

		if (lineBroken) {
			renderStep('living_check');
		} else {
			renderStep('outcome_article7');
		}
	}

	// ── Outcome Screen ───────────────────────────────────────────────────
	// Takes an outcome KEY, not copy. The words come from lcqData.outcomes,
	// which PHP localises from lcq_outcomes() — the same source the results
	// email is built from, so the two can never disagree.
	function renderOutcome(outcomeKey) {
		var outcomes = (typeof lcqData !== 'undefined' && lcqData.outcomes) ? lcqData.outcomes : {};
		var outcome  = outcomes[outcomeKey];

		if (!outcome) {
			// Localisation missing (script loaded out of context). Say so plainly
			// rather than rendering an empty screen.
			setProgress(100);
			questionEl.textContent = 'Your results are ready.';
			buttonsEl.innerHTML    = '<p class="cq_outcome_body">We could not load your results. Please reload the page and try again.</p>';
			return;
		}

		var headline = outcome.headline;
		var bodyText = outcome.body;

		setProgress(100);
		questionEl.textContent = headline;
		buttonsEl.innerHTML    = '';

		// Lineage summary (sidebar final update + mobile inline)
		if (state.lineage.length > 0) {
			renderLineage();
			renderInlineLineage();
		}

		// Body text
		var bodyEl = document.createElement('p');
		bodyEl.className   = 'cq_outcome_body';
		bodyEl.textContent = bodyText;
		buttonsEl.appendChild(bodyEl);

		// Legal disclaimer
		buttonsEl.insertAdjacentHTML('beforeend', '\
			<div class="cq_disclaimer">\
				<strong>Important:</strong> This quiz is strictly informational and does not constitute legal advice. It is not written or reviewed by an attorney. Luxembourg citizenship law is complex and individual circumstances vary\u2014naturalization history, adoption, border changes, and other factors can all affect eligibility. For a conclusive determination, please contact the <a href="https://mj.gouvernement.lu/en/particuliers/nationalite.html" target="_blank" rel="noopener noreferrer">Luxembourg Ministry of Justice</a> directly.\
			</div>\
		');

		// Email results. The list opt-in is a separate, unchecked choice —
		// asking for your results is not the same as asking for our newsletter.
		var offerOptin = (typeof lcqData !== 'undefined') && lcqData.offer_optin;

		var optinHtml = offerOptin ? '\
			<div class="cq_optin">\
				<label class="cq_optin__label" for="lcq-subscribe">\
					<input type="checkbox" id="lcq-subscribe" class="cq_optin__checkbox">\
					<span class="cq_optin__text">Also send me occasional TCLAS news and event invitations.</span>\
					<span class="cq_optin__note">We’ll email you a confirmation link before adding you to anything.</span>\
				</label>\
			</div>\
		' : '';

		buttonsEl.insertAdjacentHTML('beforeend', '\
			<div class="cq_email_section">\
				<p class="cq_email_label">Send yourself a copy of these results:</p>\
				<div class="cq_form_group">\
					<label class="cq_sr_only" for="lcq-email">Your email address</label>\
					<input type="email" id="lcq-email" class="cq_input_field" placeholder="your@email.com" autocomplete="email">\
				</div>\
				' + optinHtml + '\
				<button id="lcq-email-btn" class="cq_option_btn cq_submit_btn">Send Results</button>\
				<p id="lcq-email-status" class="cq_email_status" aria-live="polite" role="status"></p>\
			</div>\
		');

		document.getElementById('lcq-email-btn').addEventListener('click', function () {
			var emailInput  = document.getElementById('lcq-email');
			var statusEl    = document.getElementById('lcq-email-status');
			var subscribeEl = document.getElementById('lcq-subscribe');
			var btn         = document.getElementById('lcq-email-btn');
			var email       = emailInput ? emailInput.value.trim() : '';

			if (!email) {
				statusEl.textContent = 'Please enter your email address.';
				return;
			}

			statusEl.textContent = 'Sending\u2026';
			btn.disabled = true;

			var payload = new FormData();
			payload.append('action',  'lcq_send_results');
			payload.append('nonce',   (typeof lcqData !== 'undefined') ? lcqData.nonce : '');
			payload.append('email',   email);
			// Send the outcome KEY and a structured lineage \u2014 never prose. The
			// server owns every word that ends up in the email.
			payload.append('outcome', outcomeKey);
			payload.append('lineage', JSON.stringify(buildLineagePayload()));
			if (state.userBornBefore1969 !== null) {
				payload.append('user_before_1969', state.userBornBefore1969 ? '1' : '0');
			}
			if (subscribeEl && subscribeEl.checked) {
				payload.append('subscribe', '1');
			}

			var ajaxUrl = (typeof lcqData !== 'undefined')
				? lcqData.ajax_url
				: '/wp-admin/admin-ajax.php';

			fetch(ajaxUrl, { method: 'POST', body: payload })
				.then(function (r)   { return r.json(); })
				.then(function (res) {
					if (res.success) {
						var msg = (res.data && res.data.message) || 'Results sent! Check your inbox.';
						if (res.data && res.data.confirming) {
							msg += ' We\u2019ve also sent a link to confirm your email list signup.';
						}
						statusEl.textContent = msg;
					} else {
						btn.disabled = false;
						statusEl.textContent = (typeof res.data === 'string' && res.data)
							? res.data
							: 'Something went wrong\u2014please try again.';
					}
				})
				.catch(function () {
					btn.disabled = false;
					statusEl.textContent = 'Something went wrong\u2014please try again.';
				});
		});

		// Restart button
		var restartBtn = document.createElement('button');
		restartBtn.className   = 'cq_restart_btn mt-4';
		restartBtn.textContent = 'Start over';
		restartBtn.addEventListener('click', function () {
			history = [];
			state   = freshState();
			hideSidebar();
			renderStep('gate_ancestor');
		});
		buttonsEl.appendChild(restartBtn);
	}

	// ── Start the engine ─────────────────────────────────────────────────
	renderStep('gate_ancestor');

})();
