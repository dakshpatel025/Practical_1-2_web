window.StudentHub = window.StudentHub || {
    previousFocus: null,
    openModal: function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            this.previousFocus = document.activeElement;
            modal.classList.add('active');
            modal.style.display = 'flex';
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            const closeButton = modal.querySelector('.modal-close-btn');
            if (closeButton) closeButton.focus();
        }
    },
    closeModal: function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            if (this.previousFocus && this.previousFocus.focus) this.previousFocus.focus();
        }
    }
};

function organizeNavigation() {
    const nav = document.querySelector('#nav nav');
    if (!nav || document.getElementById('sideNav')) return;

    const rootPrefix = window.location.pathname.includes('/html/') ||
        window.location.pathname.includes('/backend/') ? '' : 'html/';
    const appSectionIndex = window.location.pathname.search(/\/(?:frontend|backend)\//);
    const appRoot = appSectionIndex >= 0
        ? window.location.pathname.slice(0, appSectionIndex + 1)
        : '';
    let signedInStudent = null;
    try {
        signedInStudent = JSON.parse(sessionStorage.getItem('studenthub_logged_in_student') || 'null');
    } catch (error) {
        signedInStudent = null;
    }
    const isAdmin = Boolean(signedInStudent && signedInStudent.role === 'admin');
    ['About', 'Contact'].concat(isAdmin ? ['Admin'] : []).forEach(function(label) {
        if (Array.from(nav.querySelectorAll('a.input')).some(function(link) {
            return link.textContent.trim().toLowerCase() === label.toLowerCase();
        })) return;
        const link = document.createElement('a');
        link.href = label === 'Admin' && appRoot !== ''
            ? appRoot + 'backend/admin.php'
            : rootPrefix + label.toLowerCase() + '.html';
        link.className = 'input';
        link.textContent = label;
        nav.appendChild(link);
    });

    const links = Array.from(nav.querySelectorAll('a.input'));
    const mainLabels = ['home', 'about', 'profile', 'attendance', 'time - table', 'semester result'];
    const firstSideLabels = ['family detail', 'payment detail', 'course', 'student hostel'];
    const secondSideLabels = ['events', 'faq', 'feedback', 'contact', 'event form'];
    const accountLabels = ['register', 'sign in', 'sign out', 'admin'];

    nav.innerHTML = '';
    nav.className = 'main-nav';

    const mainLinks = document.createElement('div');
    mainLinks.className = 'main-nav-links';

    links.filter(function(link) {
        return mainLabels.includes(link.textContent.trim().toLowerCase());
    }).forEach(function(link) {
        mainLinks.appendChild(link);
    });

    const themeButton = document.createElement('button');
    themeButton.type = 'button';
    themeButton.className = 'theme-toggle';
    themeButton.setAttribute('aria-pressed', 'false');
    themeButton.setAttribute('aria-label', 'Switch to dark theme');
    themeButton.textContent = 'Dark theme';
    mainLinks.appendChild(themeButton);

    nav.appendChild(mainLinks);

    function createGroup(title, groupLinks) {
        const group = document.createElement('section');
        group.className = 'side-nav-group';

        const heading = document.createElement('button');
        heading.type = 'button';
        heading.className = 'side-nav-title';
        heading.textContent = title;
        heading.setAttribute('aria-expanded', 'false');

        const linkList = document.createElement('div');
        linkList.className = 'side-nav-links';
        groupLinks.forEach(function(link) { linkList.appendChild(link); });

        heading.addEventListener('click', function() {
            const isOpen = group.classList.toggle('group-open');
            heading.setAttribute('aria-expanded', String(isOpen));
        });

        group.appendChild(heading);
        group.appendChild(linkList);
        return group;
    }

    const sideNav = document.createElement('aside');
    sideNav.id = 'sideNav';
    sideNav.setAttribute('aria-label', 'Secondary navigation');

    const toggleButton = document.createElement('button');
    toggleButton.type = 'button';
    toggleButton.className = 'side-nav-toggle';
    toggleButton.setAttribute('aria-label', 'Open secondary navigation');
    toggleButton.setAttribute('aria-expanded', 'false');
    toggleButton.textContent = '☰';

    const sideNavContent = document.createElement('div');
    sideNavContent.className = 'side-nav-content';

    const byLabel = function(labels) {
        return links.filter(function(link) {
            return labels.includes(link.textContent.trim().toLowerCase());
        });
    };

    sideNavContent.appendChild(createGroup('Student services', byLabel(firstSideLabels)));
    sideNavContent.appendChild(createGroup('Events and help', byLabel(secondSideLabels)));

    const accountLinks = byLabel(accountLabels).filter(function(link) {
        const label = link.textContent.trim().toLowerCase();
        return label !== 'sign in' && label !== 'sign out' && (label !== 'admin' || isAdmin);
    });
    const isSignedIn = Boolean(
        sessionStorage.getItem('studenthub_logged_in_user') ||
        sessionStorage.getItem('studenthub_logged_in_student')
    );
    if (isSignedIn) {
        const signOut = document.createElement('a');
        signOut.href = window.location.pathname.includes('/backend/')
            ? '../../backend/api/logout_process.php'
            : rootPrefix === 'html/'
                ? '../backend/api/logout_process.php'
                : '../../backend/api/logout_process.php';
        signOut.className = 'input';
        signOut.textContent = 'Sign Out';
        signOut.addEventListener('click', function() {
            sessionStorage.removeItem('studenthub_logged_in_user');
            sessionStorage.removeItem('studenthub_logged_in_student');
        });
        accountLinks.push(signOut);
    } else {
        const signIn = document.createElement('a');
        signIn.href = rootPrefix + 'login.html';
        signIn.className = 'input';
        signIn.textContent = 'Sign In';
        accountLinks.push(signIn);
    }

    sideNavContent.appendChild(createGroup('Account', accountLinks));
    sideNav.appendChild(toggleButton);
    sideNav.appendChild(sideNavContent);

    toggleButton.addEventListener('click', function() {
        const isOpen = sideNav.classList.toggle('side-nav-open');
        document.body.classList.toggle('side-nav-open', isOpen);
        toggleButton.setAttribute('aria-expanded', String(isOpen));
        toggleButton.setAttribute('aria-label', isOpen ? 'Close secondary navigation' : 'Open secondary navigation');
    });

    document.getElementById('nav').appendChild(sideNav);
    document.body.classList.add('has-side-nav');
}

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-backdrop')) {
        window.StudentHub.closeModal(e.target.id);
    }
    if (e.target.classList.contains('modal-close-btn') || e.target.classList.contains('modal-close-btn-action')) {
        const modal = e.target.closest('.modal-backdrop');
        if (modal) window.StudentHub.closeModal(modal.id);
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const activeModals = document.querySelectorAll('.modal-backdrop.active, .modal-backdrop[style*="display: flex"]');
        activeModals.forEach(function(m) {
            window.StudentHub.closeModal(m.id);
        });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    organizeNavigation();
    const themeButton = document.querySelector('.theme-toggle');
    if (!themeButton) return;

    const savedTheme = localStorage.getItem('studenthub_theme');
    const setTheme = function(isDark) {
        document.documentElement.dataset.theme = isDark ? 'dark' : 'light';
        themeButton.setAttribute('aria-pressed', String(isDark));
        themeButton.setAttribute('aria-label', isDark ? 'Switch to light theme' : 'Switch to dark theme');
        themeButton.textContent = isDark ? 'Light theme' : 'Dark theme';
    };
    setTheme(savedTheme === 'dark');
    themeButton.addEventListener('click', function() {
        const isDark = document.documentElement.dataset.theme !== 'dark';
        localStorage.setItem('studenthub_theme', isDark ? 'dark' : 'light');
        setTheme(isDark);
    });
});
