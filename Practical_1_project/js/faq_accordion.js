const DEFAULT_FAQS = [
  {
    "id": 1,
    "category": "Academic",
    "question": "What is the minimum attendance requirement to appear for university exams?",
    "answer": "Students must keep at least 80% attendance in each subject to be eligible for the end-semester examinations.",
    "tags": ["attendance", "exams"]
  },
  {
    "id": 2,
    "category": "Fees",
    "question": "How can I pay my semester fees online?",
    "answer": "Use the Payment Details page to pay by net banking, UPI, or card before the due date.",
    "tags": ["fees", "payment"]
  },
  {
    "id": 3,
    "category": "Hostel",
    "question": "What are the hostel timings and rules?",
    "answer": "Hostel gates close at 9:30 PM on weekdays and 10:00 PM on weekends, with prior approval needed for late entry.",
    "tags": ["hostel", "rules"]
  },
  {
    "id": 4,
    "category": "Portal",
    "question": "How do I reset my StudentHub password?",
    "answer": "Use the Forgot password option on the login page and follow the reset link sent to your university email.",
    "tags": ["login", "password"]
  },
  {
    "id": 5,
    "category": "Events",
    "question": "How do I register for campus events?",
    "answer": "Open the Events page, choose a program, and register from the available activity details section.",
    "tags": ["events", "registration"]
  }
];
document.addEventListener('DOMContentLoaded', function () {
    const JSON_URL = '../data/faqs.json';
    const CACHE_KEY = 'studenthub_cached_faqs_v1';
    let allFaqs = [];
    let filteredFaqs = [];
    let activeCategory = 'ALL';
    const faqAccordionContainer = document.getElementById('faqAccordionContainer');
    const faqSearchInput = document.getElementById('faqSearchInput');
    const categoryTabsContainer = document.getElementById('faqCategoryTabs');
    const faqLoading = document.getElementById('faqLoading');
    const faqCount = document.getElementById('faqCount');
    async function loadFaqs() {
        if (faqLoading) faqLoading.style.display = 'block';
        try {
            const response = await fetch(JSON_URL);
            if (!response.ok) throw new Error('HTTP ' + response.status);
            const data = await response.json();
            allFaqs = data;
            localStorage.setItem(CACHE_KEY, JSON.stringify(data));
        } catch (err) {
            console.warn('FAQ fetch notice (file:// protocol or offline), using built-in dataset:', err);
            const cached = localStorage.getItem(CACHE_KEY);
            if (cached) {
                try {
                    allFaqs = JSON.parse(cached);
                } catch (e) {
                    allFaqs = DEFAULT_FAQS;
                }
            } else {
                allFaqs = DEFAULT_FAQS;
            }
        } finally {
            if (faqLoading) faqLoading.style.display = 'none';
            setupFaqUI();
        }
    }
    function setupFaqUI() {
        createCategoryTabs();
        filterAndRenderFaqs();
    }
    function createCategoryTabs() {
        if (!categoryTabsContainer) return;
        const categories = ['ALL', ...Array.from(new Set(allFaqs.map(f => f.category)))];
        categoryTabsContainer.innerHTML = '';
        categories.forEach(cat => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = `faq-tab-btn ${cat === activeCategory ? 'active' : ''}`;
            btn.textContent = (cat === 'ALL') ? 'All Topics' : cat;
            btn.addEventListener('click', () => {
                activeCategory = cat;
                document.querySelectorAll('.faq-tab-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                filterAndRenderFaqs();
            });
            categoryTabsContainer.appendChild(btn);
        });
    }
    function filterAndRenderFaqs() {
        const query = (faqSearchInput ? faqSearchInput.value : '').trim().toLowerCase();
        filteredFaqs = allFaqs.filter(faq => {
            const matchesCategory = (activeCategory === 'ALL') || (faq.category === activeCategory);
            const matchesQuery = faq.question.toLowerCase().includes(query) ||
                                 faq.answer.toLowerCase().includes(query) ||
                                 (faq.tags && faq.tags.some(t => t.toLowerCase().includes(query)));
            return matchesCategory && matchesQuery;
        });
        renderAccordion();
    }
    function renderAccordion() {
        if (!faqAccordionContainer) return;
        faqAccordionContainer.innerHTML = '';
        if (faqCount) {
            faqCount.textContent = `${filteredFaqs.length} Question${filteredFaqs.length === 1 ? '' : 's'}`;
        }
        if (filteredFaqs.length === 0) {
            faqAccordionContainer.innerHTML = `
                <div style="text-align: center; padding: 40px; background: var(--bg-card); border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                    <div style="font-size: 32px; margin-bottom: 8px;">🤔</div>
                    <h3 style="color: var(--text-primary); margin: 0 0 8px;">No questions found</h3>
                    <p style="color: var(--text-secondary); font-size: 13px; margin: 0;">Try typing a different keyword or choosing another topic.</p>
                </div>
            `;
            return;
        }
        filteredFaqs.forEach((faq, index) => {
            const item = document.createElement('div');
            item.className = 'faq-accordion-item';
            const itemId = `faq-header-${faq.id || index}`;
            const panelId = `faq-panel-${faq.id || index}`;
            item.innerHTML = `
                <button type="button"
                        class="faq-question-btn"
                        id="${itemId}"
                        aria-expanded="false"
                        aria-controls="${panelId}">
                    <span class="faq-question-text">
                        <span class="faq-badge">${escapeHtml(faq.category)}</span>
                        ${escapeHtml(faq.question)}
                    </span>
                    <span class="faq-icon" aria-hidden="true">+</span>
                </button>
                <div id="${panelId}"
                     class="faq-answer-panel"
                     role="region"
                     aria-labelledby="${itemId}">
                    <div class="faq-answer-content">
                        <p style="margin: 0;">${escapeHtml(faq.answer)}</p>
                    </div>
                </div>
            `;
            faqAccordionContainer.appendChild(item);
        });
        document.querySelectorAll('.faq-question-btn').forEach(button => {
            button.addEventListener('click', function () {
                const isExpanded = this.getAttribute('aria-expanded') === 'true';
                const panel = this.nextElementSibling;
                const icon = this.querySelector('.faq-icon');
                document.querySelectorAll('.faq-question-btn').forEach(otherBtn => {
                    if (otherBtn !== this) {
                        otherBtn.setAttribute('aria-expanded', 'false');
                        otherBtn.nextElementSibling.style.maxHeight = null;
                        const otherIcon = otherBtn.querySelector('.faq-icon');
                        if (otherIcon) otherIcon.textContent = '+';
                    }
                });
                if (!isExpanded) {
                    this.setAttribute('aria-expanded', 'true');
                    panel.style.maxHeight = panel.scrollHeight + 'px';
                    if (icon) icon.textContent = '−';
                } else {
                    this.setAttribute('aria-expanded', 'false');
                    panel.style.maxHeight = null;
                    if (icon) icon.textContent = '+';
                }
            });
        });
    }
    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
    if (faqSearchInput) {
        faqSearchInput.addEventListener('input', filterAndRenderFaqs);
    }
    loadFaqs();
});
