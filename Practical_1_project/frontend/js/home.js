document.addEventListener('DOMContentLoaded', function () {
    const slides = [
        {
            title: 'Welcome to StudentHub Portal',
            description: 'Access student profile, verify registered courses, check daily lecture attendance, and view examination results.'
        },
        {
            title: 'Cognizance 2026 - Annual Tech Fest',
            description: 'Register for state-level coding competitions, hackathons, robotics challenges, and paper presentations.'
        },
        {
            title: 'Semester Examination Schedule Published',
            description: 'Check your timetable, block allocations, and minimum 80% attendance eligibility on the portal.'
        }
    ];
    let currentSlide = 0;
    const title = document.getElementById('slideTitle');
    const description = document.getElementById('slideDesc');

    function showSlide(index) {
        title.textContent = slides[index].title;
        description.textContent = slides[index].description;
    }

    document.getElementById('previousSlide').addEventListener('click', function () {
        currentSlide = (currentSlide - 1 + slides.length) % slides.length;
        showSlide(currentSlide);
    });
    document.getElementById('nextSlide').addEventListener('click', function () {
        currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
    });
    document.getElementById('noticeClose').addEventListener('click', function () {
        document.getElementById('noticeBanner').remove();
    });
});
