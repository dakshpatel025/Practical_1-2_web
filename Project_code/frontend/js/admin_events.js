document.addEventListener('DOMContentLoaded', () => {
    const apiUrl = '../../backend/api/admin_events.php';
    const form = document.getElementById('eventForm');
    const tableBody = document.getElementById('eventTableBody');
    const message = document.getElementById('adminMessage');
    const count = document.getElementById('eventCount');
    const actionInput = document.getElementById('eventAction');
    const idInput = document.getElementById('eventId');
    const submitButton = document.getElementById('eventSubmitButton');
    const cancelButton = document.getElementById('cancelEventEdit');
    const formTitle = document.getElementById('event-form-title');
    const posterArea = document.getElementById('currentEventPosterArea');
    if (!form || !tableBody || !message || !count || !actionInput || !idInput ||
        !submitButton || !cancelButton || !formTitle || !posterArea) {
        return;
    }

    const csrfToken = form.elements.namedItem('csrf_token').value;

    async function requestJson(url, options) {
        const response = await fetch(url, options);
        let result;
        try {
            result = await response.json();
        } catch {
            throw new Error('The server returned an invalid response. Please try again.');
        }
        if (!response.ok || !result.success) {
            throw new Error(result.message || 'The event request failed.');
        }
        return result;
    }

    function showMessage(text, state = 'success') {
        message.textContent = text;
        message.classList.toggle('admin-message-error', state === 'error');
        message.classList.toggle('admin-message-success', state === 'success');
        message.classList.toggle('admin-message-loading', state === 'loading');
        message.hidden = false;
        message.setAttribute('role', state === 'error' ? 'alert' : 'status');
    }

    function showError(error, prefix = '') {
        const detail = error instanceof Error ? error.message : 'Please try again.';
        showMessage(`${prefix}${detail}`, 'error');
    }

    function clearMessage() {
        message.textContent = '';
        message.hidden = true;
        message.classList.remove('admin-message-error', 'admin-message-success', 'admin-message-loading');
    }

    function createCell(text) {
        const cell = document.createElement('td');
        cell.textContent = text;
        return cell;
    }

    function renderEvents(events) {
        tableBody.replaceChildren();
        count.textContent = `${events.length} event${events.length === 1 ? '' : 's'}`;
        if (events.length === 0) {
            const row = document.createElement('tr');
            const cell = createCell('No events have been added.');
            cell.colSpan = 8;
            cell.className = 'empty-students';
            row.appendChild(cell);
            tableBody.appendChild(row);
            return;
        }

        for (const event of events) {
            const row = document.createElement('tr');
            const posterCell = document.createElement('td');
            if (event.poster_url) {
                const image = document.createElement('img');
                image.className = 'event-poster-thumbnail';
                image.src = event.poster_url;
                image.alt = `Poster for ${event.title}`;
                posterCell.appendChild(image);
            } else {
                posterCell.textContent = 'No poster';
            }
            row.appendChild(posterCell);

            const titleCell = document.createElement('td');
            titleCell.append(document.createTextNode(event.title));
            titleCell.appendChild(document.createElement('br'));
            const category = document.createElement('span');
            category.className = 'field-hint';
            category.textContent = event.category;
            titleCell.appendChild(category);
            row.appendChild(titleCell);

            const dateCell = document.createElement('td');
            dateCell.append(document.createTextNode(event.date));
            dateCell.appendChild(document.createElement('br'));
            dateCell.append(document.createTextNode(event.time));
            row.appendChild(dateCell);
            row.appendChild(createCell(event.venue));
            row.appendChild(createCell(`${event.registered} / ${event.seats}`));
            row.appendChild(createCell(event.organizer));

            const statusCell = document.createElement('td');
            const statusBadge = document.createElement('span');
            statusBadge.className = 'status-badge';
            statusBadge.textContent = event.badge;
            statusCell.appendChild(statusBadge);
            row.appendChild(statusCell);

            const actionsCell = document.createElement('td');
            actionsCell.className = 'student-actions';
            const editLink = document.createElement('a');
            editLink.className = 'admin-secondary-button event-edit-button';
            editLink.href = `../../backend/admin.php?edit_event=${encodeURIComponent(event.id)}#events`;
            editLink.dataset.eventId = event.id;
            editLink.textContent = 'Edit';
            actionsCell.appendChild(editLink);

            const deleteForm = document.createElement('form');
            deleteForm.className = 'event-delete-form';
            deleteForm.action = apiUrl;
            deleteForm.method = 'post';
            const token = document.createElement('input');
            token.type = 'hidden';
            token.name = 'csrf_token';
            token.value = csrfToken;
            deleteForm.appendChild(token);
            const action = document.createElement('input');
            action.type = 'hidden';
            action.name = 'action';
            action.value = 'delete_event';
            deleteForm.appendChild(action);
            const eventId = document.createElement('input');
            eventId.type = 'hidden';
            eventId.name = 'event_id';
            eventId.value = event.id;
            deleteForm.appendChild(eventId);
            const deleteButton = document.createElement('button');
            deleteButton.type = 'submit';
            deleteButton.className = 'admin-danger-button';
            deleteButton.textContent = 'Delete';
            deleteForm.appendChild(deleteButton);
            actionsCell.appendChild(deleteForm);
            row.appendChild(actionsCell);
            tableBody.appendChild(row);
        }
    }

    async function refreshEvents() {
        tableBody.setAttribute('aria-busy', 'true');
        try {
            const result = await requestJson(apiUrl, { headers: { Accept: 'application/json' } });
            renderEvents(result.events);
        } finally {
            tableBody.removeAttribute('aria-busy');
        }
    }

    function setEditMode(event) {
        form.elements.namedItem('event_title').value = event.title;
        form.elements.namedItem('event_category').value = event.category;
        form.elements.namedItem('event_date').value = event.date;
        form.elements.namedItem('start_time').value = event.start_time;
        form.elements.namedItem('venue').value = event.venue;
        form.elements.namedItem('seat_capacity').value = event.seats;
        form.elements.namedItem('organizer').value = event.organizer;
        form.elements.namedItem('description').value = event.description;
        form.elements.namedItem('badge').value = event.badge;
        form.elements.namedItem('poster').value = '';
        posterArea.replaceChildren();
        if (event.poster_url) {
            const image = document.createElement('img');
            image.src = event.poster_url;
            image.alt = `Current poster for ${event.title}`;
            posterArea.appendChild(image);
            const label = document.createElement('label');
            label.className = 'poster-remove-option';
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.name = 'remove_poster';
            checkbox.value = '1';
            label.append(checkbox, document.createTextNode('Remove current poster'));
            posterArea.appendChild(label);
        }
        idInput.value = event.id;
        actionInput.value = 'update_event';
        formTitle.textContent = 'Edit event';
        submitButton.textContent = 'Save event changes';
        cancelButton.hidden = false;
        document.getElementById('events').scrollIntoView({ behavior: 'smooth' });
        formTitle.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function resetEventForm() {
        form.reset();
        idInput.value = '0';
        actionInput.value = 'create_event';
        formTitle.textContent = 'Add an event';
        submitButton.textContent = 'Add event';
        cancelButton.hidden = true;
        posterArea.replaceChildren();
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const action = actionInput.value;
        const successMessage = action === 'create_event' ? 'Event added successfully.' : 'Event updated successfully.';
        submitButton.disabled = true;
        cancelButton.disabled = true;
        showMessage(action === 'create_event' ? 'Adding event...' : 'Saving event changes...', 'loading');
        try {
            await requestJson(apiUrl, {
                method: 'POST',
                body: new FormData(form),
                headers: { Accept: 'application/json' }
            });
            resetEventForm();
            showMessage('Refreshing event list...', 'loading');
            try {
                await refreshEvents();
                showMessage(successMessage);
            } catch (error) {
                showError(error, `${successMessage} However, the event list could not be refreshed. `);
            }
        } catch (error) {
            showError(error);
        } finally {
            submitButton.disabled = false;
            cancelButton.disabled = false;
        }
    });

    tableBody.addEventListener('click', async (event) => {
        const editButton = event.target.closest('.event-edit-button');
        if (!editButton) return;
        event.preventDefault();
        editButton.setAttribute('aria-disabled', 'true');
        showMessage('Loading event details...', 'loading');
        try {
            const result = await requestJson(`${apiUrl}?id=${encodeURIComponent(editButton.dataset.eventId)}`, {
                headers: { Accept: 'application/json' }
            });
            setEditMode(result.event);
            showMessage('Event details loaded. Make changes and save when ready.');
        } catch (error) {
            showError(error);
        } finally {
            editButton.removeAttribute('aria-disabled');
        }
    });

    tableBody.addEventListener('submit', async (event) => {
        const deleteForm = event.target.closest('.event-delete-form');
        if (!deleteForm) return;
        event.preventDefault();
        if (!window.confirm('Delete this event and its registrations?')) return;
        const deleteButton = deleteForm.querySelector('button[type="submit"]');
        deleteButton.disabled = true;
        showMessage('Deleting event...', 'loading');
        try {
            const result = await requestJson(apiUrl, {
                method: 'POST',
                body: new FormData(deleteForm),
                headers: { Accept: 'application/json' }
            });
            if (idInput.value === String(result.event_id)) resetEventForm();
            showMessage('Refreshing event list...', 'loading');
            try {
                await refreshEvents();
                showMessage(result.message);
            } catch (error) {
                showError(error, `${result.message} However, the event list could not be refreshed. `);
            }
        } catch (error) {
            showError(error);
        } finally {
            deleteButton.disabled = false;
        }
    });

    cancelButton.addEventListener('click', resetEventForm);

    showMessage('Loading events...', 'loading');
    refreshEvents()
        .then(clearMessage)
        .catch((error) => showError(error, 'Could not load events. '));
});
