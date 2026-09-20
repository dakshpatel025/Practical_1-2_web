window.StudentHub = window.StudentHub || {
    openModal: function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    },
    closeModal: function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }
};

function organizeNavigation() {
    const nav = document.querySelector('#nav nav');
    if (!nav || document.getElementById('sideNav')) return;

    const links = Array.from(nav.querySelectorAll('a.input'));
    const mainLabels = ['home', 'profile', 'attendance', 'time - table', 'semester result'];
    const firstSideLabels = ['family detail', 'payment detail', 'course', 'student hostel'];
    const secondSideLabels = ['events', 'faq', 'feedback'];
    const accountLabels = ['register'];
    const rootPrefix = window.location.pathname.includes('/html/') ? '' : 'html/';
    const isLandingPage = !window.location.pathname.includes('/html/');

    nav.innerHTML = '';
    nav.className = 'main-nav';

    const mainLinks = document.createElement('div');
    mainLinks.className = 'main-nav-links';

    links.filter(function(link) {
        return mainLabels.includes(link.textContent.trim().toLowerCase());
    }).forEach(function(link) {
        mainLinks.appendChild(link);
    });

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

    const accountLinks = byLabel(accountLabels);
    if (isLandingPage) {
        const signIn = document.createElement('a');
        signIn.href = rootPrefix + 'login.html';
        signIn.className = 'input';
        signIn.textContent = 'Sign In';
        accountLinks.push(signIn);
    } else {
        const signOut = document.createElement('a');
        signOut.href = 'login.html';
        signOut.className = 'input';
        signOut.textContent = 'Sign Out';
        accountLinks.push(signOut);
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
        e.target.classList.remove('active');
        e.target.style.display = 'none';
        document.body.style.overflow = '';
    }
    if (e.target.classList.contains('modal-close-btn') || e.target.classList.contains('modal-close-btn-action')) {
        const modal = e.target.closest('.modal-backdrop');
        if (modal) {
            modal.classList.remove('active');
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const activeModals = document.querySelectorAll('.modal-backdrop.active, .modal-backdrop[style*="display: flex"]');
        activeModals.forEach(function(m) {
            m.classList.remove('active');
            m.style.display = 'none';
        });
        document.body.style.overflow = '';
    }
});

document.addEventListener('DOMContentLoaded', function() {
    organizeNavigation();
});
