document.addEventListener('DOMContentLoaded', function () {
    const JSON_URL = '../../backend/api/events.php';
    let allEvents = [];
    let filteredEvents = [];
    let currentPage = 1;
    const itemsPerPage = 6;
    let selectedEvent = null;
    const eventsContainer = document.getElementById('eventsContainer');
    const loadingSpinner = document.getElementById('eventsLoading');
    const errorMessage = document.getElementById('eventsError');
    const searchInput = document.getElementById('eventSearchInput');
    const categorySelect = document.getElementById('eventCategorySelect');
    const sortSelect = document.getElementById('eventSortSelect');
    const paginationContainer = document.getElementById('paginationControls');
    const totalResultsCount = document.getElementById('totalResultsCount');
    const retryBtn = document.getElementById('retryFetchBtn');
    let controlsInitialized = false;
    async function loadEventsData() {
        showLoading(true);
        hideError();
        try {
            const response = await fetch(JSON_URL);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const result = await response.json();
            if (!result.success) throw new Error(result.message || 'Could not load events.');
            allEvents = result.events;
        } catch (err) {
            console.error('Could not load database events:', err);
            allEvents = [];
            showError('Could not load events from the database. Start Apache and MySQL, then try again.');
        } finally {
            showLoading(false);
            initializeData();
        }
    }
    function showLoading(show) {
        if (loadingSpinner) {
            loadingSpinner.style.display = show ? 'block' : 'none';
        }
    }
    function showError(msg) {
        if (errorMessage) {
            errorMessage.textContent = msg;
            errorMessage.style.display = 'block';
        }
    }
    function hideError() {
        if (errorMessage) {
            errorMessage.style.display = 'none';
        }
    }
    function initializeData() {
        populateCategories();
        applyFiltersAndSort();
        if (controlsInitialized) return;
        controlsInitialized = true;
        if (searchInput) {
            searchInput.addEventListener('input', () => {
                currentPage = 1;
                applyFiltersAndSort();
            });
        }
        if (categorySelect) {
            categorySelect.addEventListener('change', () => {
                currentPage = 1;
                applyFiltersAndSort();
            });
        }
        if (sortSelect) {
            sortSelect.addEventListener('change', () => {
                currentPage = 1;
                applyFiltersAndSort();
            });
        }
        if (retryBtn) {
            retryBtn.addEventListener('click', loadEventsData);
        }
    }
    function populateCategories() {
        if (!categorySelect) return;
        const currentVal = categorySelect.value || 'ALL';
        const categories = Array.from(new Set(allEvents.map(e => e.category))).filter(Boolean);
        categorySelect.innerHTML = '<option value="ALL">All Categories</option>';
        categories.forEach(cat => {
            const opt = document.createElement('option');
            opt.value = cat;
            opt.textContent = cat;
            if (cat === currentVal) opt.selected = true;
            categorySelect.appendChild(opt);
        });
    }
    function applyFiltersAndSort() {
        const query = (searchInput ? searchInput.value : '').trim().toLowerCase();
        const selectedCat = categorySelect ? categorySelect.value : 'ALL';
        const sortBy = sortSelect ? sortSelect.value : 'date_asc';
        filteredEvents = allEvents.filter(event => {
            const matchesCategory = (selectedCat === 'ALL') || (event.category === selectedCat);
            const matchesQuery = !query ||
                event.title.toLowerCase().includes(query) ||
                event.description.toLowerCase().includes(query) ||
                event.venue.toLowerCase().includes(query) ||
                (event.organizer && event.organizer.toLowerCase().includes(query));
            return matchesCategory && matchesQuery;
        });
        filteredEvents.sort((a, b) => {
            if (sortBy === 'date_asc') return new Date(a.date) - new Date(b.date);
            if (sortBy === 'date_desc') return new Date(b.date) - new Date(a.date);
            if (sortBy === 'title_asc') return a.title.localeCompare(b.title);
            if (sortBy === 'seats_desc') return b.seats - a.seats;
            return 0;
        });
        if (totalResultsCount) {
            totalResultsCount.textContent = `Showing ${filteredEvents.length} of ${allEvents.length} events`;
        }
        renderEvents();
        renderPagination();
    }
    function renderEvents() {
        if (!eventsContainer) return;
        eventsContainer.innerHTML = '';
        if (filteredEvents.length === 0) {
            eventsContainer.innerHTML = `
                <div style="grid-column: 1 / -1; text-align: center; padding: 50px 20px; background: var(--bg-card); border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                    <div style="font-size: 38px; margin-bottom: 12px;">📅</div>
                    <h3 style="color: var(--text-primary); margin: 0 0 8px;">No events found</h3>
                    <p style="color: var(--text-secondary); font-size: 14px; margin: 0;">Try adjusting your keyword search or category filter.</p>
                </div>
            `;
            return;
        }
        const startIndex = (currentPage - 1) * itemsPerPage;
        const paginatedEvents = filteredEvents.slice(startIndex, startIndex + itemsPerPage);
        paginatedEvents.forEach(event => {
            const card = document.createElement('div');
            card.className = 'event-card';
            const categoryColor = getCategoryColor(event.category);
            const availableSeats = Math.max(0, event.seats - (event.registered || 0));
            const isFull = availableSeats === 0;
            card.innerHTML = `
                <div>
                    ${event.poster_url ? `<img class="event-poster" src="${escapeHtml(event.poster_url)}" alt="Poster for ${escapeHtml(event.title)}" loading="lazy">` : ''}
                    <div class="event-card-header">
                        <span class="event-category-badge" style="background-color: ${categoryColor};">
                            ${escapeHtml(event.category)}
                        </span>
                        <span class="event-status-badge">
                            ${isFull ? '🔴 Housefull' : `🟢 ${availableSeats} Seats Left`}
                        </span>
                    </div>
                    <h3 class="event-title">${escapeHtml(event.title)}</h3>
                    <p class="event-desc">${escapeHtml(event.description)}</p>
                </div>
                <div>
                    <div class="event-meta-info">
                        <div class="meta-row">
                            <span><strong>📅 Date:</strong> ${formatDate(event.date)}</span>
                            <span><strong>⏰ Time:</strong> ${escapeHtml(event.time)}</span>
                        </div>
                        <div class="meta-row">
                            <span><strong>📍 Venue:</strong> ${escapeHtml(event.venue)}</span>
                        </div>
                        <div class="meta-row">
                            <span><strong>👥 Organizer:</strong> ${escapeHtml(event.organizer || 'CHARUSAT')}</span>
                        </div>
                    </div>
                    <div class="event-card-actions">
                        <button type="button" class="btn btn-secondary view-details-btn" data-id="${event.id}">
                            View Details
                        </button>
                        <button type="button" class="btn btn-primary register-event-btn" data-id="${event.id}" ${isFull ? 'disabled' : ''}>
                            ${isFull ? 'Full' : 'Register &rarr;'}
                        </button>
                    </div>
                </div>
            `;
            eventsContainer.appendChild(card);
        });
        document.querySelectorAll('.view-details-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = parseInt(this.getAttribute('data-id'), 10);
                openEventModal(id);
            });
        });
        document.querySelectorAll('.register-event-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = parseInt(this.getAttribute('data-id'), 10);
                quickRegisterEvent(id);
            });
        });
    }
    function renderPagination() {
        if (!paginationContainer) return;
        paginationContainer.innerHTML = '';
        const totalPages = Math.ceil(filteredEvents.length / itemsPerPage);
        if (totalPages <= 1) return;
        const prevBtn = document.createElement('button');
        prevBtn.type = 'button';
        prevBtn.className = 'pagination-btn';
        prevBtn.textContent = '← Prev';
        prevBtn.disabled = currentPage === 1;
        prevBtn.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                renderEvents();
                renderPagination();
                scrollToEventsTop();
            }
        });
        paginationContainer.appendChild(prevBtn);
        for (let i = 1; i <= totalPages; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.type = 'button';
            pageBtn.className = `pagination-btn ${i === currentPage ? 'active' : ''}`;
            pageBtn.textContent = i;
            pageBtn.addEventListener('click', () => {
                currentPage = i;
                renderEvents();
                renderPagination();
                scrollToEventsTop();
            });
            paginationContainer.appendChild(pageBtn);
        }
        const nextBtn = document.createElement('button');
        nextBtn.type = 'button';
        nextBtn.className = 'pagination-btn';
        nextBtn.textContent = 'Next →';
        nextBtn.disabled = currentPage === totalPages;
        nextBtn.addEventListener('click', () => {
            if (currentPage < totalPages) {
                currentPage++;
                renderEvents();
                renderPagination();
                scrollToEventsTop();
            }
        });
        paginationContainer.appendChild(nextBtn);
    }
    function scrollToEventsTop() {
        const topEl = document.getElementById('title');
        if (topEl) {
            topEl.scrollIntoView({ behavior: 'smooth' });
        }
    }
    function openEventModal(id) {
        selectedEvent = allEvents.find(e => e.id === id);
        if (!selectedEvent) return;
        document.getElementById('modalEventTitle').textContent = selectedEvent.title;
        document.getElementById('modalEventCategory').textContent = selectedEvent.category;
        document.getElementById('modalEventDate').textContent = formatDate(selectedEvent.date);
        document.getElementById('modalEventTime').textContent = selectedEvent.time;
        document.getElementById('modalEventVenue').textContent = selectedEvent.venue;
        document.getElementById('modalEventOrganizer').textContent = selectedEvent.organizer || 'CHARUSAT';
        document.getElementById('modalEventSeats').textContent = `${selectedEvent.seats - (selectedEvent.registered || 0)} seats remaining (Total: ${selectedEvent.seats})`;
        document.getElementById('modalEventDesc').textContent = selectedEvent.description;
        const confirmBtn = document.getElementById('modalConfirmRegisterBtn');
        const availableSeats = selectedEvent.seats - (selectedEvent.registered || 0);
        if (confirmBtn) {
            confirmBtn.disabled = availableSeats <= 0;
            confirmBtn.textContent = availableSeats <= 0 ? 'Housefull' : 'Confirm Registration';
            confirmBtn.onclick = function() {
                confirmModalRegistration();
            };
        }
        if (window.StudentHub && window.StudentHub.openModal) {
            window.StudentHub.openModal('eventDetailsModal');
        } else {
            const m = document.getElementById('eventDetailsModal');
            if (m) {
                m.classList.add('active');
                m.style.display = 'flex';
            }
        }
    }
    function confirmModalRegistration() {
        if (!selectedEvent) return;
        window.location.href = `event_register.html?event_id=${encodeURIComponent(selectedEvent.id)}`;
    }
    function quickRegisterEvent(id) {
        const ev = allEvents.find(e => e.id === id);
        if (!ev) return;
        const available = ev.seats - (ev.registered || 0);
        if (available <= 0) {
            return;
        }
        window.location.href = `event_register.html?event_id=${encodeURIComponent(ev.id)}`;
    }
    function getCategoryColor(cat) {
        const map = {
            'Technical': '#0284c7',
            'Sports': '#16a34a',
            'Workshop': '#7c3aed',
            'Seminar': '#d97706',
            'Cultural': '#e11d48',
            'Networking': '#0d9488',
            'Social': '#059669',
            'Career': '#4f46e5',
            'Entrepreneurship': '#ca8a04',
            'Health': '#0891b2'
        };
        return map[cat] || '#03398a';
    }
    function formatDate(isoStr) {
        if (!isoStr) return '';
        try {
            const parts = isoStr.split('-');
            if (parts.length === 3) {
                const d = new Date(parts[0], parts[1] - 1, parts[2]);
                return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            }
        } catch (e) {}
        return isoStr;
    }
    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
    loadEventsData();
});
