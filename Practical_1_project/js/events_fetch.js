const DEFAULT_EVENTS = [
  {
    "id": 1,
    "title": "Spoural 2026 - Annual Sports Festival",
    "category": "Sports",
    "date": "2026-10-12",
    "time": "08:30 AM",
    "venue": "University Sports Complex",
    "seats": 500,
    "registered": 340,
    "organizer": "CHARUSAT Sports Council",
    "description": "Annual university sports meet with cricket, football, volleyball, athletics, and chess competitions.",
    "badge": "Upcoming"
  },
  {
    "id": 2,
    "title": "Cognizance 2026 - National Tech Fest",
    "category": "Technical",
    "date": "2026-10-25",
    "time": "09:00 AM",
    "venue": "CSPIT Auditorium",
    "seats": 800,
    "registered": 620,
    "organizer": "Department of Computer Science & Engineering",
    "description": "Grand technical symposium featuring hackathons, coding contests, robotics wars, and paper presentations.",
    "badge": "Featured"
  },
  {
    "id": 3,
    "title": "Full-Stack Web Development Bootcamp",
    "category": "Workshop",
    "date": "2026-11-05",
    "time": "10:00 AM",
    "venue": "DEPSTAR Lab 301",
    "seats": 60,
    "registered": 58,
    "organizer": "Google Developer Student Club (GDSC)",
    "description": "Hands-on masterclass on modern frontend architectures, Fetch API, async JavaScript, and cloud deployment.",
    "badge": "Few Seats Left"
  },
  {
    "id": 4,
    "title": "AI & Machine Learning in Healthcare",
    "category": "Seminar",
    "date": "2026-11-12",
    "time": "02:00 PM",
    "venue": "CMPICA Seminar Hall",
    "seats": 150,
    "registered": 95,
    "organizer": "Research & Development Cell",
    "description": "Keynote address by industry experts on predictive diagnosis, medical imaging, and deep learning algorithms.",
    "badge": "Open"
  },
  {
    "id": 5,
    "title": "Vrund 2026 - Navratri Garba Celebration",
    "category": "Cultural",
    "date": "2026-10-18",
    "time": "07:00 PM",
    "venue": "CHARUSAT Central Lawn",
    "seats": 2000,
    "registered": 1850,
    "organizer": "Student Cultural Council",
    "description": "Traditional grand Raas-Garba festival with live orchestra, awards for best attire, and campus unity.",
    "badge": "Popular"
  },
  {
    "id": 6,
    "title": "Cybersecurity & Ethical Hacking Hands-on",
    "category": "Workshop",
    "date": "2026-11-20",
    "time": "11:00 AM",
    "venue": "IT Lab 204",
    "seats": 50,
    "registered": 45,
    "organizer": "Cyber Cell CHARUSAT",
    "description": "Learn network sniffing, vulnerability scanning, penetration testing basics, and defensive counter-measures.",
    "badge": "Open"
  },
  {
    "id": 7,
    "title": "Annual Alumni Meet & Mentorship Drive",
    "category": "Networking",
    "date": "2026-12-02",
    "time": "10:30 AM",
    "venue": "University Convention Center",
    "seats": 400,
    "registered": 280,
    "organizer": "CHARUSAT Alumni Association",
    "description": "Connect with industry leaders, distinguished alumni, and gain direct mentorship for campus placements.",
    "badge": "Open"
  },
  {
    "id": 8,
    "title": "Clean Campus & Green Earth Drive",
    "category": "Social",
    "date": "2026-10-02",
    "time": "07:30 AM",
    "venue": "CHARUSAT Main Gate",
    "seats": 300,
    "registered": 240,
    "organizer": "NSS & Nature Club",
    "description": "Tree plantation, e-waste collection drive, and cleanliness awareness rally across the campus perimeter.",
    "badge": "Completed"
  },
  {
    "id": 9,
    "title": "24-Hour Smart India Internal Hackathon",
    "category": "Technical",
    "date": "2026-11-28",
    "time": "09:00 AM",
    "venue": "DEPSTAR Innovation Hub",
    "seats": 120,
    "registered": 120,
    "organizer": "Incubation & Entrepreneurship Cell",
    "description": "Intensive team programming competition solving real-world problem statements for Smart India Hackathon.",
    "badge": "Housefull"
  },
  {
    "id": 10,
    "title": "Cloud Computing with AWS & DevOps",
    "category": "Workshop",
    "date": "2026-12-08",
    "time": "01:30 PM",
    "venue": "CSE Cloud Lab",
    "seats": 80,
    "registered": 62,
    "organizer": "AWS Student Club",
    "description": "Architecting serverless web services, Docker containerization, CI/CD pipelines, and S3 static hosting.",
    "badge": "Open"
  },
  {
    "id": 11,
    "title": "Inter-College Debate & Model UN",
    "category": "Cultural",
    "date": "2026-12-14",
    "time": "10:00 AM",
    "venue": "IIIM Conference Hall",
    "seats": 100,
    "registered": 75,
    "organizer": "Literary & Debating Society",
    "description": "Debate on geopolitical developments, digital privacy rights, and ethical governance policies.",
    "badge": "Open"
  },
  {
    "id": 12,
    "title": "Resume Building & Mock Technical Interviews",
    "category": "Career",
    "date": "2026-12-18",
    "time": "02:30 PM",
    "venue": "Training & Placement Cell",
    "seats": 150,
    "registered": 140,
    "organizer": "Career Development Centre",
    "description": "One-on-one resume feedback, algorithmic coding interview practice, and HR behavioral preparation.",
    "badge": "Few Seats Left"
  },
  {
    "id": 13,
    "title": "Mobile App Development with Flutter",
    "category": "Technical",
    "date": "2027-01-08",
    "time": "10:00 AM",
    "venue": "DEPSTAR Mobile Computing Lab",
    "seats": 70,
    "registered": 42,
    "organizer": "Mobile Developers Guild",
    "description": "Cross-platform mobile application development for Android and iOS using Dart and Flutter framework.",
    "badge": "Open"
  },
  {
    "id": 14,
    "title": "Charusat Blood Donation & Health Checkup Camp",
    "category": "Social",
    "date": "2027-01-15",
    "time": "09:00 AM",
    "venue": "CHARUSAT Hospital Lawn",
    "seats": 400,
    "registered": 310,
    "organizer": "Youth Red Cross & NSS",
    "description": "Voluntary blood donation drive in association with Indian Red Cross Society with complimentary health checkup.",
    "badge": "Open"
  },
  {
    "id": 15,
    "title": "Startup Pitch Fest & Angel Funding Expo",
    "category": "Entrepreneurship",
    "date": "2027-01-22",
    "time": "10:00 AM",
    "venue": "University Auditorium",
    "seats": 250,
    "registered": 180,
    "organizer": "CHARUSAT Innovation & Startup Center",
    "description": "Showcase your prototype to venture capitalists, angel investors, and win seed funding up to INR 2.5 Lakhs.",
    "badge": "Featured"
  },
  {
    "id": 16,
    "title": "Yoga & Mental Wellness Workshop",
    "category": "Health",
    "date": "2027-01-29",
    "time": "06:30 AM",
    "venue": "University Open Amphitheatre",
    "seats": 200,
    "registered": 130,
    "organizer": "Student Welfare Association",
    "description": "Guided pranayama, stress relief exercises, and mindfulness techniques tailored for academic excellence.",
    "badge": "Open"
  }
];
document.addEventListener('DOMContentLoaded', function () {
    const JSON_URL = '../data/events.json';
    const CACHE_KEY = 'studenthub_cached_events_v1';
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
    async function loadEventsData() {
        showLoading(true);
        hideError();
        try {
            const response = await fetch(JSON_URL);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            allEvents = data;
            localStorage.setItem(CACHE_KEY, JSON.stringify(data));
        } catch (err) {
            console.warn('Fetch note (file:// protocol or offline), using built-in dataset:', err);
            const cached = localStorage.getItem(CACHE_KEY);
            if (cached) {
                try {
                    allEvents = JSON.parse(cached);
                } catch (e) {
                    allEvents = DEFAULT_EVENTS;
                }
            } else {
                allEvents = DEFAULT_EVENTS;
            }
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
        selectedEvent.registered = (selectedEvent.registered || 0) + 1;
        if (window.StudentHub && window.StudentHub.closeModal) {
            window.StudentHub.closeModal('eventDetailsModal');
        } else {
            const m = document.getElementById('eventDetailsModal');
            if (m) {
                m.classList.remove('active');
                m.style.display = 'none';
            }
        }
        alert(`🎉 Registration Confirmed!\nYou have successfully registered for:\n"${selectedEvent.title}"\nVenue: ${selectedEvent.venue}\nDate: ${selectedEvent.date}`);
        renderEvents();
    }
    function quickRegisterEvent(id) {
        const ev = allEvents.find(e => e.id === id);
        if (!ev) return;
        const available = ev.seats - (ev.registered || 0);
        if (available <= 0) {
            alert('Sorry, this event is already full!');
            return;
        }
        ev.registered = (ev.registered || 0) + 1;
        alert(`🎉 Successfully Registered!\nEvent: "${ev.title}"\nDate: ${ev.date}`);
        renderEvents();
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
