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
        const profile = student.profile || {};

        setValue('stdId', student.id || '');
        setValue('fullName', profile.name || student.name || '');
        setValue('dispName', profile.displayName || student.name || '');
        setValue('uniName', profile.university || 'CHARUSAT');
        setValue('bPlace', profile.birthPlace || student.birthPlace || student.city || '');
        setSelectValue('instSelect', profile.institute || student.institute || 'DEPSTAR');
        setSelectValue('progSelect', profile.degree || student.degree || 'B.Tech CSE');
        setRadioValue('gender', student.gender || 'Male');
        setRadioValue('nat', profile.nationality || 'Indian');
        setValue('mName', profile.motherName || (student.family && student.family.mother && student.family.mother.name) || '');
        setValue('bDate', student.birthDate || student.dob || '');
        setSelectValue('casteSelect', profile.caste || 'OPEN');
        setSelectValue('religionSelect', profile.religion || 'Hindu');
        const d2d = document.getElementById('d2dCheck');
        if (d2d) d2d.checked = Boolean(profile.isD2D);
        setImagePreview('photoPreview', profile.photo, 'No Photo Uploaded');
    }

    function populateFamily(student) {
        if (!student) return;

        const father = (student.family && student.family.father) || student.father || {};
        const mother = (student.family && student.family.mother) || student.mother || {};

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
        setFamilyPhoto('father', father.photo);
        setFamilyPhoto('mother', mother.photo);
    }

    function setImagePreview(containerId, source, emptyText) {
        const container = document.getElementById(containerId);
        if (!container) return;
        container.replaceChildren();
        if (!source) {
            const text = document.createElement('span');
            text.textContent = emptyText;
            container.appendChild(text);
            return;
        }
        const image = document.createElement('img');
        image.src = source;
        image.alt = emptyText;
        image.style.width = '100%';
        image.style.height = '100%';
        image.style.objectFit = 'cover';
        container.appendChild(image);
    }

    function setFamilyPhoto(parent, source) {
        const image = document.getElementById(parent + 'Preview');
        const text = document.getElementById(parent + 'Text');
        if (!image || !text) return;
        image.src = source || '';
        image.style.display = source ? 'block' : 'none';
        text.style.display = source ? 'none' : 'block';
    }

    function getPreviewPhoto(containerId) {
        const source = document.querySelector(`#${containerId} img`)?.src || '';
        return source.startsWith('data:image/jpeg;base64,') ? source : '';
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
        setValue('hostelQuery', hostel.query || '');

        const messRadio = document.querySelectorAll('input[name="mess"]');
        messRadio.forEach(radio => {
            radio.checked = radio.value === (hostel.messIncluded ? 'Yes' : 'No');
        });

        const facilityBoxes = document.querySelectorAll('.checkboxes input[type="checkbox"]');
        const selectedFacilities = hostel.facilities || ['Wi-Fi', 'Laundry', 'Parking', '24/7 Security'];
        facilityBoxes.forEach(box => {
            box.checked = selectedFacilities.includes(box.parentElement.textContent.trim());
        });

        const comfortableRadios = document.querySelectorAll('input[name="comfortable"]');
        comfortableRadios.forEach(radio => {
            radio.checked = radio.value === (hostel.comfortable === false ? 'No' : 'Yes');
        });
    }

    function fullNameFromFields(prefix) {
        return ['FirstName', 'MiddleName', 'LastName']
            .map(part => document.getElementById(prefix + part)?.value.trim())
            .filter(Boolean)
            .join(' ');
    }

    function getProfileUpdate() {
        return {
            profile: {
                name: document.getElementById('fullName').value.trim(),
                displayName: document.getElementById('dispName').value.trim(),
                motherName: document.getElementById('mName').value.trim(),
                birthDate: document.getElementById('bDate').value,
                birthPlace: document.getElementById('bPlace').value.trim(),
                gender: document.querySelector('input[name="gender"]:checked')?.value || '',
                isD2D: document.getElementById('d2dCheck').checked,
                university: document.getElementById('uniName').value,
                institute: document.getElementById('instSelect').value,
                degree: document.getElementById('progSelect').value,
                caste: document.getElementById('casteSelect').value,
                religion: document.getElementById('religionSelect').value,
                nationality: document.querySelector('input[name="nat"]:checked')?.value || '',
                photo: getPreviewPhoto('photoPreview')
            }
        };
    }

    function getFamilyUpdate() {
        return {
            family: {
                father: {
                    name: fullNameFromFields('father'),
                    mobile: document.getElementById('fatherMobile').value.trim(),
                    occupation: document.getElementById('fatherOccupation').value.trim(),
                    photo: getPreviewPhoto('fatherPreview')
                },
                mother: {
                    name: fullNameFromFields('mother'),
                    mobile: document.getElementById('motherMobile').value.trim(),
                    occupation: document.getElementById('motherOccupation').value.trim(),
                    photo: getPreviewPhoto('motherPreview')
                }
            }
        };
    }

    function getHostelUpdate() {
        return {
            hostel: {
                name: document.getElementById('hostelName').value.trim(),
                address: document.getElementById('hostelAddress').value.trim(),
                contact: document.getElementById('hostelMobile').value.trim(),
                type: document.getElementById('hostelType').value,
                roomNumber: document.getElementById('roomNumber').value.trim(),
                roomType: document.getElementById('roomType').value,
                ownerName: fullNameFromFields('owner'),
                ownerMobile: document.getElementById('ownerMobile').value.trim(),
                wardenName: document.getElementById('wardenName').value.trim(),
                wardenMobile: document.getElementById('wardenMobile').value.trim(),
                monthlyFees: Number(document.getElementById('monthlyFees').value || 0),
                messIncluded: document.querySelector('input[name="mess"]:checked')?.value === 'Yes',
                facilities: Array.from(document.querySelectorAll('.checkboxes input:checked'))
                    .map(input => input.value),
                query: document.getElementById('hostelQuery').value.trim(),
                comfortable: document.querySelector('input[name="comfortable"]:checked')?.value === 'Yes'
            }
        };
    }

    function showSaveStatus(message, isError = false) {
        let status = document.getElementById('studentDataMessage');
        if (!status) {
            status = document.createElement('p');
            status.id = 'studentDataMessage';
            status.setAttribute('role', 'status');
            status.setAttribute('aria-live', 'polite');
            const container = document.querySelector('.profile-save-bar, .family-actions, .hostel-buttons') ||
                document.querySelector('main');
            if (container) container.appendChild(status);
        }
        if (status) {
            status.textContent = message;
            status.style.color = isError ? '#b42318' : '#16794b';
        }
    }

    function connectSaveButton(buttonId, collectUpdates) {
        const button = document.getElementById(buttonId);
        if (!button) return;
        button.addEventListener('click', async event => {
            event.preventDefault();
            button.disabled = true;
            showSaveStatus('Saving your information...');
            try {
                const response = await fetch('../../backend/api/profile_process.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(collectUpdates())
                });
                const result = await response.json();
                if (response.status === 401) {
                    window.location.href = 'login.html';
                    return;
                }
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Could not save your information.');
                }
                sessionStorage.setItem(STORAGE_KEY, JSON.stringify(result.student));
                sessionStorage.setItem('studenthub_logged_in_user', result.student.id);
                showSaveStatus('Your information was saved to your account.');
            } catch (error) {
                console.error('Student profile save failed', error);
                showSaveStatus(error.message || 'Could not save your information.', true);
            } finally {
                button.disabled = false;
            }
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
    window.populateStudentData = async function () {
        let student = getLoggedInStudent();
        if (!student) return;
        try {
            const response = await fetch('../../backend/api/profile_process.php', { credentials: 'same-origin' });
            const result = await response.json();
            if (response.status === 401) {
                sessionStorage.removeItem(STORAGE_KEY);
                sessionStorage.removeItem('studenthub_logged_in_user');
                window.location.href = 'login.html';
                return;
            }
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Could not load your saved profile.');
            }
            student = result.student;
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify(student));
            sessionStorage.setItem('studenthub_logged_in_user', student.id);
        } catch (error) {
            console.error('Student profile load failed', error);
            showSaveStatus(error.message || 'Could not load your saved profile.', true);
        }

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
        connectSaveButton('saveProfileButton', getProfileUpdate);
        connectSaveButton('saveFamilyButton', getFamilyUpdate);
        connectSaveButton('saveHostelButton', getHostelUpdate);
    };

    document.addEventListener('DOMContentLoaded', function () {
        window.populateStudentData();
    });
})();
