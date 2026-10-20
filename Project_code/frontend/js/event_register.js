    // ─── State ────────────────────────────────────────────────────────────────
    let allEvents = [];
    let selectedEvent = null;

    // ─── Load Events for Selection ────────────────────────────────────────────
    async function loadEvents() {
        const grid = document.getElementById('eventGrid');
        try {
            const resp = await fetch('../../backend/api/events.php?t=' + Date.now());
            if (!resp.ok) throw new Error();
            const result = await resp.json();
            if (!result.success) throw new Error(result.message || 'Could not load events.');
            allEvents = result.events;

            if (!allEvents.length) {
                grid.innerHTML = '<div class="empty-state">No events available.</div>';
                return;
            }

            grid.innerHTML = allEvents.map(ev => {
                const avail = ev.seats - ev.registered;
                const isFull = avail <= 0;
                const seatsClass = isFull ? 'full' : (avail <= 10 ? 'low' : 'ok');
                const seatsText  = isFull ? 'Fully Booked' : `${avail} seats left`;
                return `
                    <div class="event-select-card ${isFull ? 'full' : ''}" data-id="${ev.id}" onclick="selectEvent(${ev.id})">
                        <div class="ev-check">✓</div>
                        ${ev.poster_url ? `<img class="event-select-poster" src="${escHtml(ev.poster_url)}" alt="" loading="lazy">` : ''}
                        <div class="ev-title">${escHtml(ev.title)}</div>
                        <div class="ev-meta">📅 ${escHtml(ev.date)} &nbsp; ⏰ ${escHtml(ev.time)}</div>
                        <div class="ev-meta">📍 ${escHtml(ev.venue)}</div>
                        <span class="ev-seats ${seatsClass}">${seatsText}</span>
                    </div>
                `;
            }).join('');
        } catch {
            grid.innerHTML = '<div class="empty-state">Could not load events. Ensure XAMPP is running.</div>';
        }
    }

    function selectEvent(eventId) {
        selectedEvent = allEvents.find(e => e.id === eventId) || null;
        if (!selectedEvent) return;

        // Update card selection
        document.querySelectorAll('.event-select-card').forEach(c => c.classList.remove('selected'));
        const card = document.querySelector(`.event-select-card[data-id="${eventId}"]`);
        if (card) card.classList.add('selected');

        document.getElementById('hiddenEventId').value = selectedEvent.id;

        // Show info panel
        const avail = selectedEvent.seats - selectedEvent.registered;
        document.getElementById('selectedEventName').textContent  = selectedEvent.title;
        document.getElementById('selectedEventDate').textContent  = selectedEvent.date + ' at ' + selectedEvent.time;
        document.getElementById('selectedEventSeats').textContent = avail + ' seats remaining';
        document.getElementById('selectedEventPanel').style.display = 'block';

        document.getElementById('errEventSelect').style.display = 'none';
    }

    // ─── Form Submit ──────────────────────────────────────────────────────────
    document.getElementById('eventRegForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        clearAllErrors();

        // Validate event selection
        if (!selectedEvent) {
            const errEl = document.getElementById('errEventSelect');
            errEl.textContent = 'Please select an event from the cards above.';
            errEl.style.display = 'block';
            document.getElementById('eventGrid').scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        const btn = document.getElementById('evSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Registering...';

        const formData = new FormData(this);

        try {
            const response = await fetch('../../backend/api/event_register_process.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                showSuccess(data);
                this.reset();
                selectedEvent = null;
                document.querySelectorAll('.event-select-card').forEach(c => c.classList.remove('selected'));
                document.getElementById('selectedEventPanel').style.display = 'none';
                await loadEvents();
            } else {
                showServerError(data.message, data.errors || {});
            }
        } catch (err) {
            showServerError('Network error: Could not reach the PHP server. Make sure XAMPP is running.', {});
        }

        btn.disabled = false;
        btn.innerHTML = 'Register for Event →';
    });

    document.getElementById('evResetBtn').addEventListener('click', function () {
        setTimeout(() => {
            clearAllErrors();
            document.getElementById('serverErrorBanner').style.display = 'none';
            document.getElementById('serverSuccessBanner').style.display = 'none';
        }, 50);
    });

    function clearAllErrors() {
        document.querySelectorAll('.field-err').forEach(el => { el.style.display = 'none'; el.textContent = ''; });
        document.querySelectorAll('.input-invalid').forEach(el => el.classList.remove('input-invalid'));
    }

    function showFieldError(key, msg) {
        const map = {
            participant_name:   'errEvName',
            participant_email:  'errEvEmail',
            participant_mobile: 'errEvMobile',
            student_id:         'errEvStudentId',
            department:         'errEvDept',
            year_of_study:      'errEvYear',
            special_note:       'errEvNote',
            terms:              'errEvTerms'
        };
        const inputMap = {
            participant_name:   'evName',
            participant_email:  'evEmail',
            participant_mobile: 'evMobile',
            student_id:         'evStudentId',
            department:         'evDept',
            year_of_study:      'evYearStudy'
        };
        if (map[key]) { const el = document.getElementById(map[key]); if (el) { el.textContent = msg; el.style.display = 'block'; } }
        if (inputMap[key]) { const inp = document.getElementById(inputMap[key]); if (inp) inp.classList.add('input-invalid'); }
    }

    function showServerError(mainMsg, errors) {
        const banner = document.getElementById('serverErrorBanner');
        let html = `<strong>❌ ${mainMsg}</strong>`;
        const keys = Object.keys(errors);
        if (keys.length) {
            html += '<ul>';
            keys.forEach(k => { html += `<li>${errors[k]}</li>`; showFieldError(k, errors[k]); });
            html += '</ul>';
        }
        banner.innerHTML = html;
        banner.style.display = 'block';
        document.getElementById('serverSuccessBanner').style.display = 'none';
        banner.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function showSuccess(data) {
        const banner = document.getElementById('serverSuccessBanner');
        if (data.cancelled) {
            banner.textContent = data.message;
            banner.style.display = 'block';
            document.getElementById('serverErrorBanner').style.display = 'none';
            banner.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        banner.innerHTML = `
            <div style="font-size:28px;margin-bottom:6px;">🎉</div>
            <div style="font-size:16px;margin-bottom:4px;">Registration Successful!</div>
            <div class="reg-id-box">${escHtml(data.reg_id)}</div>
            <p>You are registered for <strong>${escHtml(data.event_title)}</strong>.
               <span style="color:var(--warning);font-weight:600;">Seats remaining: ${data.seats_remaining}</span></p>
            <button class="btn btn-secondary" type="button" data-cancel-reg="${escHtml(data.reg_id)}">Cancel Registration</button>
        `;
        banner.style.display = 'block';
        document.getElementById('serverErrorBanner').style.display = 'none';
        banner.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    document.getElementById('serverSuccessBanner').addEventListener('click', event => {
        const button = event.target.closest('[data-cancel-reg]');
        if (button) deleteRegistration(button.dataset.cancelReg, button);
    });

    function escHtml(str) {
        if (!str && str !== 0) return '—';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }

    async function deleteRegistration(regId, button) {
        if (!confirm('Cancel this registration and restore one seat?')) return;
        button.disabled = true;
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('reg_id', regId);
        try {
            const response = await fetch('../../backend/api/event_register_process.php', { method: 'POST', body: formData });
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error(data.message || 'Could not cancel registration.');
            showSuccess(data);
            await loadEvents();
        } catch (error) {
            showServerError(error.message || 'Could not delete registration.', {});
            button.disabled = false;
        }
    }

    // ─── Init ─────────────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', async () => {
        await loadEvents();
        const eventId = Number(new URLSearchParams(window.location.search).get('event_id'));
        if (eventId) {
            selectEvent(eventId);
            document.getElementById('eventRegForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
