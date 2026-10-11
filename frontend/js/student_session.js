(function () {
    const STORAGE_KEY = 'studenthub_logged_in_student';

    function safeValue(value, fallback = '') {
        return value === undefined || value === null ? fallback : value;
    }

    function setText(id, value) {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = safeValue(value, '');
        }
    }

    function setValue(id, value) {
        const element = document.getElementById(id);
        if (element) {
            element.value = safeValue(value, '');
        }
    }

    function setRadioValue(name, value) {
        const radios = document.querySelectorAll(`input[type="radio"][name="${name}"]`);
        radios.forEach(radio => {
            radio.checked = radio.value === value;
        });
    }

    function setSelectValue(id, value) {
        const element = document.getElementById(id);
        if (element && value) {
            element.value = value;
        }
    }

    function splitFullName(fullName) {
        const parts = String(fullName || '').trim().split(/\s+/).filter(Boolean);
        return {
            first: parts[0] || '',
            middle: parts.length > 2 ? parts.slice(1, -1).join(' ') : '',
            last: parts[parts.length - 1] || ''
        };
    }

    function formatMoney(value) {
        const amount = Number(value || 0);
        return `₹ ${amount.toLocaleString('en-IN')}`;
    }

    function getLoggedInStudent() {
        const raw = sessionStorage.getItem(STORAGE_KEY);
        if (!raw) {
            window.location.href = 'login.html';
            return null;
        }

        try {
            return JSON.parse(raw);
        } catch (error) {
            console.error('Invalid student data in session', error);
            window.location.href = 'login.html';
            return null;
        }
    }

    function populateProfile(student) {
        if (!student) return;

        setValue('stdId', student.id || '');
        setValue('fullName', student.name || '');
        setValue('dispName', student.name || '');
        setValue('uniName', 'CHARUSAT');
        setValue('bPlace', student.birthPlace || student.city || '');
        setSelectValue('instSelect', student.institute || 'DEPSTAR');
        setSelectValue('progSelect', student.degree || 'B.Tech CSE');
        setRadioValue('gender', student.gender || 'Male');
        setRadioValue('nat', 'Indian');
        setValue('mName', (student.mother && student.mother.name) || '');
        setValue('bDate', student.birthDate || '2006-05-14');
    }

    function populateFamily(student) {
        if (!student) return;

        const father = student.father || {};
        const mother = student.mother || {};

        const fatherName = splitFullName(father.name || '');
        const motherName = splitFullName(mother.name || '');

        setValue('fatherFirstName', fatherName.first);
        setValue('fatherMiddleName', fatherName.middle);
        setValue('fatherLastName', fatherName.last);
        setValue('fatherMobile', father.mobile || '');
        setValue('fatherOccupation', father.occupation || '');

        setValue('motherFirstName', motherName.first);
        setValue('motherMiddleName', motherName.middle);
        setValue('motherLastName', motherName.last);
        setValue('motherMobile', mother.mobile || '');
        setValue('motherOccupation', mother.occupation || '');
    }

    function populateHostel(student) {
        if (!student) return;
        const hostel = student.hostel || {};

        setValue('hostelName', hostel.name || 'CHARUSAT Campus Hostel Block A');
        setValue('hostelAddress', hostel.address || 'CHARUSAT Campus, Changa - 388421');
        setValue('hostelMobile', hostel.contact || '9876543210');
        setValue('hostelType', hostel.type || 'College Hostel');
        setValue('roomNumber', hostel.roomNumber || 'A-312');
        setValue('roomType', hostel.roomType || 'Double Sharing');

        const ownerName = splitFullName(hostel.ownerName || 'Rameshbhai S. Patel');
        setValue('ownerFirstName', ownerName.first);
        setValue('ownerMiddleName', ownerName.middle);
        setValue('ownerLastName', ownerName.last);
        setValue('ownerMobile', hostel.ownerMobile || '9824056789');
        setValue('wardenName', hostel.wardenName || 'Pravinbhai Vankar');
        setValue('wardenMobile', hostel.wardenMobile || '9879512340');
        setValue('monthlyFees', hostel.monthlyFees || 6500);

        const messRadio = document.querySelectorAll('input[name="mess"]');
        messRadio.forEach(radio => {
            radio.checked = hostel.messIncluded ? radio.value === 'Yes' || radio.defaultValue === 'Yes' : radio.value === 'No';
        });

        const facilityBoxes = document.querySelectorAll('.checkboxes input[type="checkbox"]');
        const selectedFacilities = hostel.facilities || ['Wi-Fi', 'Laundry', 'Parking', '24/7 Security'];
        facilityBoxes.forEach(box => {
            box.checked = selectedFacilities.includes(box.parentElement.textContent.trim());
        });

        const comfortableRadios = document.querySelectorAll('input[name="comfortable"]');
        comfortableRadios.forEach(radio => {
            radio.checked = radio.value === 'Yes';
        });
    }

    function populatePayment(student) {
        if (!student) return;
        const payment = student.payment || {};

        setText('studentEnrollment', student.id || '');
        setText('studentProgram', student.degree || student.course || 'B.Tech CSE');
        setText('totalAcademicFees', formatMoney(payment.totalAcademicFees || 390000));
        setText('feesPaidToDate', formatMoney(payment.feesPaid || 260000));
        setText('pendingDues', formatMoney(payment.pendingDues || 130000));
        setText('scholarshipReceived', formatMoney(payment.scholarshipReceived || 50000));

        const paymentRows = document.getElementById('paymentRows');
        if (paymentRows && Array.isArray(payment.semesters)) {
            paymentRows.innerHTML = payment.semesters.map((row) => {
                const paid = row.paid || 0;
                const pending = row.pending || 0;
                const scholarship = row.scholarship || 0;
                const status = pending === 0 ? '<span class="badge badge-success">Paid</span>' : '<span class="badge badge-warning">Due Soon</span>';
                return `
                    <tr>
                        <td><strong>${row.name}</strong></td>
                        <td>${formatMoney(row.total)}</td>
                        <td>${formatMoney(paid)}</td>
                        <td>${formatMoney(pending)}</td>
                        <td>${scholarship ? formatMoney(scholarship) : '—'}</td>
                        <td>${status}</td>
                    </tr>
                `;
            }).join('');
        }
    }

    function populateAttendance(student) {
        if (!student) return;
        const attendance = student.attendance || {};

        setText('attendanceSemester', student.semester || 4);
        setText('totalClasses', attendance.totalClasses || 242);
        setText('presentCount', attendance.presentCount || 209);
        setText('grossPercentage', `${attendance.grossPercentage || 86.4}%`);
        setText('examStatus', attendance.examStatus || 'Eligible (≥ 80%)');

        const tableBody = document.getElementById('attendanceTableBody');
        if (tableBody && Array.isArray(attendance.courses)) {
            tableBody.innerHTML = attendance.courses.map((course) => {
                const status = course.percentage >= 80 ? '<span class="badge badge-success">Good</span>' : '<span class="badge badge-warning">Watch</span>';
                return `
                    <tr>
                        <td><strong>${course.code}</strong> - ${course.title}</td>
                        <td><span class="badge badge-info">${course.type}</span></td>
                        <td>${course.present} / ${course.total}</td>
                        <td><strong>${course.percentage}%</strong></td>
                        <td>${status}</td>
                    </tr>
                `;
            }).join('');
        }
    }

    function populateResult(student) {
        if (!student) return;
        const result = student.result || {};

        setText('resultEnrollment', student.id || '');
        setText('resultStudentName', student.name || '');
        setText('resultDegree', student.degree || student.course || 'B.Tech CSE');
        setText('cumulativeCGPA', result.cumulativeCGPA || student.cgpa || '0');
        setText('creditsEarned', result.creditsEarned || '69.0 / 69.0');
        setText('semestersCompleted', result.semestersCompleted || '3 of 8');
        setText('classification', result.classification || 'Distinction (Top 5%)');

        const tableBody = document.getElementById('resultTableBody');
        if (tableBody && Array.isArray(result.records)) {
            tableBody.innerHTML = result.records.map((row) => `
                <tr>
                    <td><strong>${row.semester}</strong></td>
                    <td>${row.session}</td>
                    <td>${row.registeredCredits}</td>
                    <td>${row.earnedCredits}</td>
                    <td><span class="badge badge-success">${row.sgpa}</span></td>
                    <td><span class="badge badge-success">${row.status}</span></td>
                </tr>
            `).join('');
        }
    }

    window.getLoggedInStudent = getLoggedInStudent;
    window.populateStudentData = function () {
        const student = getLoggedInStudent();
        if (!student) return;

        if (document.getElementById('stdId') || document.getElementById('fullName')) {
            populateProfile(student);
        }
        if (document.getElementById('fatherFirstName') || document.getElementById('motherFirstName')) {
            populateFamily(student);
        }
        if (document.getElementById('hostelName') || document.getElementById('hostelAddress')) {
            populateHostel(student);
        }
        if (document.getElementById('studentEnrollment') || document.getElementById('totalAcademicFees')) {
            populatePayment(student);
        }
        if (document.getElementById('attendanceSemester') || document.getElementById('totalClasses')) {
            populateAttendance(student);
        }
        if (document.getElementById('resultEnrollment') || document.getElementById('cumulativeCGPA')) {
            populateResult(student);
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        window.populateStudentData();
    });
})();
