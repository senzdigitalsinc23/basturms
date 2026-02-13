<script>
//Spinner functions
function showSpinner() {
    document.getElementById('loadingSpinner').classList.remove('d-none');
}

function hideSpinner() {
    document.getElementById('loadingSpinner').classList.add('d-none');
}

    function keypressed() {
        this.addEventListener('keypress', () => {
            //alert()//addError(this, '');
        })
    }

    //Make date of birth select only date greater than 2 years and less than 30
   document.addEventListener("DOMContentLoaded", function () {
        let dobInput = document.getElementById("dob");
        let ageInput = document.getElementById("age");

        // Today's date
        let today = new Date();

        // Max allowed = today - 2 years
        let maxDate = new Date(today.getFullYear() - 2, today.getMonth(), today.getDate());

        // Min allowed = today - 25 years
        let minDate = new Date(today.getFullYear() - 25, today.getMonth(), today.getDate());

        // Convert to yyyy-mm-dd format
        let maxDateStr = maxDate.toISOString().split("T")[0];
        let minDateStr = minDate.toISOString().split("T")[0];

        // Apply restrictions
        dobInput.setAttribute("max", maxDateStr);
        dobInput.setAttribute("min", minDateStr);

        // Calculate age on DOB change
        dobInput.addEventListener("change", function () {
            let dob = new Date(dobInput.value);
            if (!isNaN(dob)) {
                let age = today.getFullYear() - dob.getFullYear();
                let m = today.getMonth() - dob.getMonth();

                // Adjust if birthday not yet reached this year
                if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
                    age--;
                }

                ageInput.value = age + " years";
            } else {
                ageInput.value = "";
            }
        });
    });



    //Event for global search button feature
    document.getElementById('btnSearch').addEventListener('click', (e) => {
        e.preventDefault();

        showSpinner()

        fetch('https://jsonplaceholder.typicode.com/posts')
            .then(response => response.json())
            .then(json => {
                console.log(json)
                //alert()
        })
        .catch(err => console.error(err))
        .finally(() => {
            // hide spinner
            hideSpinner();
        });
    })


    //Preview student data
    document.getElementById("createStudentForm").addEventListener("submit", function (e) {
    e.preventDefault();
    

    if (!validateFields(this)) {
        return;
    }

    const fieldLabels = {
    student_no: "Student Number",
    first_name: "First Name",
    last_name: "Last Name",
    other_name: "Other Name",
    gender: "Gender",
    dob: "Date of Birth",

    
    admission_no: "Admission Number",
    class_id: "Class",
    enrollment_date: "Enrollment Date",
    status: "Status",

    email: "Email Address",
    phone: "Phone Number",
    country_id: "Country",
    city: "City",
    hometown: "Hometown",
    residence: "Residence",
    gps_no: "GPS Address",
    house_no: "House Number",

    guardian_name: "Name",
    guardian_phone: "Contact",
    guardian_email: "Email",
    guardian_relationship: "Relationship",

    emergency_name: "Name",
    emergency_phone: "Contact",
    emergency_email: "Email",
    emergency_relationship: "Relationship"
};


    const form = e.target;
    const formData = new FormData(form);

    // Reset preview sections
    document.getElementById("previewPersonal").innerHTML = "";
    document.getElementById("previewContact").innerHTML = "";
    document.getElementById("previewGuardian").innerHTML = "";
    document.getElementById("previewEmergency").innerHTML = "";
    document.getElementById("previewAdmission").innerHTML = "";

    // Define groups based on form fields
    const groups = {
        personal: ['student_no','first_name','last_name','other_name','gender','dob'],
        contact: ['email','phone','country_id','city','hometown','residence','gps_no','house_no'],
        guardian: ['guardian_name','guardian_phone', 'guardian_email', 'guardian_relationship'],
        emergency: ['emergency_name','emergency_phone', 'emergency_email', 'emergency_relationship'],
        admission: ['admission_no','class_id','enrollment_date','status']
    };

    formData.forEach((value, key) => {
        let label = fieldLabels[key] || key.replace(/_/g, ' ').toUpperCase();
        let row = `
        <tr>
            <th style="width:30%;">${label}</th>
            <td>${value || '-'}</td>
        </tr>
        `;

        if (groups.personal.includes(key)) {
            document.getElementById("previewPersonal").innerHTML += row;
        } else if (groups.contact.includes(key)) {
            document.getElementById("previewContact").innerHTML += row;
        } else if (groups.guardian.includes(key)) {
            document.getElementById("previewGuardian").innerHTML += row;
        }else if (groups.emergency.includes(key)) {
            document.getElementById("previewEmergency").innerHTML += row;
        } else if (groups.admission.includes(key)) {
            document.getElementById("previewAdmission").innerHTML += row;
        }
    });


    // Show modal
    const modal = new bootstrap.Modal(document.getElementById("previewModal"));
    modal.show();

    // Handle confirm
    document.getElementById("confirmSubmit").onclick = function () {
        modal.hide();
        submitStudent(formData, form);
    };
});


    //Create new student
   function submitStudent(formData, form) {
    
    showSpinner();

    fetch("/api/students/create?apiKey=devKey123", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {console.log(result.data);
        
            alert(result.message);
            form.reset();
            console.log(result.data);
            
        } else {console.log(result.errors);
        
            Object.keys(result.errors).forEach(field => {console.log(result.errors);
            
            
                const input = form.querySelector(`[name="${field}"]`);
                if (input) {console.log(input);
                    input.classList.add("is-invalid");
                    const errorDiv = form.querySelector(`#error-${field}`);

                    addError(input, result.errors[input.name]);
                    switchToErrorTab(input);
                    if (errorDiv) errorDiv.textContent = result.errors[field];
                }
            });
        }
    })
    .catch(err => console.error(err))
    .finally(() => hideSpinner());
}

/**
 * Validate all required fields + special rules
 */
function validateFields(form) {
    let isValid = true;

    form.querySelectorAll("[data-required='true']").forEach(input => {
        let value = input.value.trim();

        // Required check
        if (!value) {
            isValid = false;
            addError(input, "This field is required");
            switchToErrorTab(input);
        } else {
            // success case (green border)
            addSuccess(input);

        }

        // Email format check
        if (input.type === "email" && value) {
            let emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                addError(input, "Please enter a valid email address");
                switchToErrorTab(input);
            } else {
                addSuccess(input);
            }
        }

        // Phone number check
        if ((input.name === "phone" || input.name === "parent_no") && value) {
            if (!/^\d{10,13}$/.test(value)) {
                isValid = false;
                addError(input, "Phone must be 10–13 digits");
                switchToErrorTab(input);
            } else {
                addSuccess(input);
            }
        }
    });

    return isValid;
}

/**
 * Add inline error + red border
 */
function addError(input, message) {
    removeFeedback(input);

    let errorDiv = document.createElement("div");
    errorDiv.classList.add("text-danger", "mt-1", "error-msg");
    errorDiv.innerText = message;
    input.closest("div").appendChild(errorDiv);

    input.classList.add("is-invalid");


    // live validation – remove error on input/change
    input.addEventListener("input", function () {
        removeFeedback(input);
    }, { once: true });
}

/**
 * Add green border for valid input
 */
function addSuccess(input) {
    removeFeedback(input);
    input.classList.add("is-valid");

    // live recheck on input
    input.addEventListener("input", function () {
        if (input.value.trim() !== "") {
            input.classList.add("is-valid");
            input.classList.remove("is-invalid");
            let errorEl = input.closest("div").querySelector(".error-msg");
            if (errorEl) errorEl.remove();
        }
    });
}

/**
 * Remove all feedback (error + success)
 */
function removeFeedback(input) {
    input.classList.remove("is-invalid", "is-valid");
    let errorEl = input.closest("div").querySelector(".error-msg");
    if (errorEl) errorEl.remove();
}

/**
 * Switch to tab containing invalid field
 */
function switchToErrorTab(input) {
    let tabPane = input.closest(".tab-pane");
    if (tabPane && !tabPane.classList.contains("active")) {
        let tabButton = document.querySelector(
            `#studentTab button[data-bs-target="#${tabPane.id}"]`
        );
        if (tabButton) {
            new bootstrap.Tab(tabButton).show();
        }
    }
}

/**
 * Clear all errors & borders
 */
function clearFeedback(form) {
    form.querySelectorAll(".error-msg").forEach(el => el.remove());
    form.querySelectorAll(".is-invalid, .is-valid").forEach(el => el.classList.remove("is-invalid", "is-valid"));
}


</script>