<script>

let validationState = {
    student_id: false,
    email: false
};


//Spinner functions
function showSpinner() {
    document.getElementById('loadingSpinner').classList.remove('d-none');
}

function hideSpinner() {
    document.getElementById('loadingSpinner').classList.add('d-none');
}

//Render pagination on page
function renderPagination(current, totalPages) {
    const pagination = document.getElementById("pagination");
    pagination.innerHTML = '';
   
    // Pagination

    // Previous button
    if (current > 1) {
        pagination.innerHTML += `<button class="btn btn-sm btn-outline-secondary me-1" onclick="loadStudents(${current - 1})">« Prev</button>`;
    } else {
        pagination.innerHTML += `<button class="btn btn-sm btn-outline-secondary me-1" disabled>« Prev</button>`;
    }

    // Page numbers (show limited range if many pages)
    const maxVisible = 5;
    let startPage = Math.max(1, current - Math.floor(maxVisible / 2));
    let endPage = Math.min(totalPages, startPage + maxVisible - 1);

    if (endPage - startPage < maxVisible - 1) {
        startPage = Math.max(1, endPage - maxVisible + 1);
    }

    for (let i = startPage; i <= endPage; i++) {
        pagination.innerHTML += `<button class="btn btn-sm ${i === current ? 'btn-primary disabled' : 'btn-outline-primary'} me-1" onclick="loadStudents(${i})">${i}</button>`;
    }

    // Next button
    if (current < totalPages) {
        pagination.innerHTML += `<button class="btn btn-sm btn-outline-secondary ms-1" onclick="loadStudents(${current + 1})">Next »</button>`;
    } else {
        pagination.innerHTML += `<button class="btn btn-sm btn-outline-secondary ms-1" disabled>Next »</button>`;
    }

    document.getElementById('pagination').innerHTML = pagination.innerHTML;

}

// Search input listener
document.getElementById('searchInput').addEventListener('keyup', function() {
    loadStudents(1);
});

 let limit = 7;

// 🔹 Rows per page event
document.getElementById('rowsPerPage').addEventListener('change', function() {
    limit = parseInt(this.value);
    const status = document.getElementById('studentStatus').value;

    loadStudents(1, '', status);
});

// 🔹 Rows per page event
document.getElementById('studentStatus').addEventListener('change', function() {
    status = this.value;

    loadStudents(1, '', status);
});

//End of search student



//Convert first letter of word to uppercase
function firstUpper(val) {
    return String(val).charAt(0).toUpperCase() + String(val).slice(1);
}

function resetForm() {
    document.getElementById('name').value = "";
    document.getElementById('email').value = "";
    document.getElementById('password').value = "";
    document.getElementById('role').selectedIndex = 0;
    return;
}


//Function for loading list of students
function loadStudents(page = 1, search = '', status = 'active') {
    let tbody = document.getElementById('tbody');
    let tableForm = '';
    let currentPage = 1;
    let searchQuery = '';

    search = document.getElementById('searchInput').value;
    status = document.getElementById('studentStatus').value;

    showSpinner();

    fetch(`/api/students?page=${page}&limit=${limit}&search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}&apiKey=devKey123`, {
        headers: { "X-Requested-With": "XMLHttpRequest" }
    })
    .then(res => res.json())
    .then(data => {console.log(data);
        
        if (data.success) {
            numbering = data.pagination.offset + 1;
            students = data.students;

            students.forEach((element) => {
                tableForm += `<tr>
                    <td>${numbering}</>
                    <td>${element.student_no}</>
                    <td>${element.first_name} ${element.other_name} ${element.last_name}</td>
                    <td>${element.email}</td>
                    <td>${element.phone}</td>
                    <td>${element.class_name ?? ''}</td>
                    
                    <td>
                        <a href="/api/students/<?=esc(${`element.id`})?>/edit" class="btn btn-sm btn-warning p-1 py-0 pb-1"><img src="/../assets/images/bootstrap-icons/pencil-square.svg" alt=""></a>
                        <a href="#"  data-bs-toggle="offcanvas" data-bs-target="#${element.last_name}Off" aria-controls="${element.last_name}Off" class="btn btn-sm btn-danger p-1 py-0 pb-1" style="color:white"><img  src="${element.admission_status == 'Active' ? '/../assets/images/bootstrap-icons/unlock-fill.svg' : '/../assets/images/bootstrap-icons/lock-fill.svg'}" alt=""></a>

                        <!--Render offcanvas per student-->
                    <div class="offcanvas offcanvas-custom"  style='border-radius: 3px' data-bs-scroll="true" data-bs-backdrop="false"  id="${element.last_name}Off" aria-labelledby="${element.last_name}OffLabel">
                        <div class="offcanvas-header" style='background-color:grey'>
                            <h5 class="offcanvas-title" id="${element.last_name}OffLabel">FREEZE STUDENT ACCOUNT</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                        </div>
                        <div class="offcanvas-body border ps-5" style='width:100%; border: 2px 2px 2px grey'>
                            <p>
                            
                                <div class='row'>
                                    <div class='col-md-12'><b>Student ID:</b> ${element.student_no}</div>
                                </div>
                                <div class='row'>
                                    <div class='col-md-12'><b>Name:</b> ${element.first_name} ${element.other_name} ${element.last_name}</div>
                                </div>
                                <div class="row"'>
                                    <form id='confirmDelete' onsubmit='deleteStudent(event)'>
                                    <div class="col-md-6">
                                    <input type='text' name='id' value='${element.id}' hidden>
                                        
                                    <label>Select Status <span style="color: red; font-weight: bolder">*</span></label>                          
                                        
                                        <select name="status" class="form-control" >
                                            <option value="active">Active</option>
                                            <option value="inactive" selected>Inactive</option>
                                            <option value="graduated">Graduated</option>
                                            <option value="pending">Pending</option>
                                            <option value="suspended">Suspended</option>
                                            <option value="transferred">Transferred</option>
                                            <option value="stopped">Stopped</option>
                                        </select>

                                        
                                    </div>
                                    <input type="submit" class="btn btn-sm btn-success p-0 px-2" id="btnConfirm" value="Delete">
                                    </form>
                                </div>
                            </p>
                        </div>
                        </div>
                    </td>
                    </tr> ` 

                numbering++;
            })

            tableForm+=`<tr><td colspan='7'>
                <div id="resultsInfo" class="text-muted"></div>
                <nav>
                    <ul class="pagination" id="pagination"></ul>
                </nav>
            </td></tr>`;
                
        }else {
            tableForm += `<tr>
                <td colspan="7"><h2 class="d-flex justify-content-center">${data.message}</h2></></td></tr>`
            tbody.innerHTML = tableForm;
            return;
        }

        tbody.innerHTML = tableForm;

        renderPagination(data.pagination.page, data.pagination.pages);
        // Results info
            const start = (data.pagination.page - 1) * limit + 1;
            const end = Math.min(start + limit - 1, data.pagination.total);
            document.getElementById('resultsInfo').textContent = 
                `Showing ${start} – ${end} of ${data.pagination.total} results`;
        return;
    })
    .catch(err => {
        console.log(err);
    })
    .finally(() => {
         // hide spinner
        hideSpinner();
    });;
}


//Export students to csv
function exportStudents (e) {
    const alertBox = document.getElementById("alert-box");
    const alertMessage = document.getElementById('alert-message');
    const btnClose = document.getElementById('btn-close');

  e.preventDefault();

  showSpinner();

  fetch("/api/students/download?apiKey=devKey123")
    .then(res =>  {
        if (!res.ok) {
            throw new Error("Network error while exporting CSV");
        }
        return res.blob(); // get response as Blob
    })
    .then(blob => {console.log(blob);     
    
        if (blob) {
           // Create a temporary download link
          const url = window.URL.createObjectURL(blob);
          const a = document.createElement("a");
          a.href = url;
          a.download = "students_export_" + new Date().toISOString().slice(0,19).replace(/:/g,"-") + ".csv";
          document.body.appendChild(a);
          a.click();
          a.remove();
          window.URL.revokeObjectURL(url);
          
          alertBox.classList.remove('alert-danger');
          alertBox.classList.add('alert-success');
          alertMessage.innerHTML = "✅ File download generated successfully"
          alertBox.hidden = false;
          btnClose.hidden = false;

          hideSpinner();

            
                
        } else {
            alertBox.classList.remove('alert-success');
            alertBox.classList.add('alert-danger');
            alertMessage.innerHTML = "❌ Export failed.";
           btnClose.hidden = false;
            alertBox.hidden = false;

            hideSpinner();
           
        }

        setTimeout( function() {
          alertMessage.innerHTML = ""
          alertBox.hidden = true;
          btnClose.click();
        }, 5000);
    });
}


//Bulk Upload Student data
function importStudents() {
    //document.getElementById("importForm").submit();

    document.getElementById("importForm").addEventListener("submit", function(e) {
    const closeUpload = document.getElementById('close-upload');

    e.preventDefault();

    showSpinner();

    let formData = new FormData(this);

      fetch("/api/students/upload?apiKey=devKey123", {
          method: "POST",
          body: formData
      })
      .then(res => res.json())
      .then(data => {console.log(data);
      
        if (data.success) {
           document.getElementById("importResult").innerHTML = data.message;

          /* alertBox.classList.remove('alert-danger');
          alertBox.classList.add('alert-success');
          alertMessage.innerHTML = "✅" . data.message;
          alertBox.hidden = false;
          btnClose.hidden = false; */

          document.getElementById('btn-preview').hidden = false;
            document.getElementById('choose_file').hidden = false;
            document.getElementById('staticBackdrop').style.width = "400px"
            document.getElementById('staticBackdrop').style.left = "45%"
            document.getElementById('staticBackdrop').style.top = "10%"
            document.getElementById('confirmImport').hidden = false;
            document.getElementById("previewTable").innerHTML = ''
            document.getElementById('preview-header').hidden = true;
            document.getElementById('confirmImport').hidden = true;
            document.getElementById('choose_file').value = '';

          
          
           loadStudents()

          
        }else {
            alertBox.classList.remove('alert-success');
            alertBox.classList.add('alert-danger');
            alertMessage.innerHTML = "❌ " . data.message;
           btnClose.hidden = false;
            alertBox.hidden = false;
            document.getElementById("importResult").innerHTML = data.message;

            
        }
          
 hideSpinner();
        setTimeout(function () {
            document.getElementById("importResult").innerHTML = '';
            
            closeUpload.click();
            
        },8000)
      });
    });

}


//Reset all fields
function resetUpload() {
    document.getElementById('btn-preview').hidden = false;
    document.getElementById('choose_file').hidden = false;
    document.getElementById('staticBackdrop').style.width = "400px"
    document.getElementById('staticBackdrop').style.left = "45%"
    document.getElementById('staticBackdrop').style.top = "10%"
    document.getElementById('confirmImport').hidden = false;
    document.getElementById("previewTable").innerHTML = ''
    document.getElementById('preview-header').hidden = true;
    document.getElementById('confirmImport').hidden = true;
    document.getElementById('choose_file').value = '';
    document.getElementById('btn-reset').hidden = true;
}


//Preview uploaded student data before submitting
function uploadPreview() {
    let form = document.getElementById("importForm");
  
    
  form.addEventListener("submit", function(e) {
    e.preventDefault();

  showSpinner();
  
    let formData = new FormData(this);
    fetch("/api/students/preview?apiKey=devKey123", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {//console.log(data);
    
        if (data.success) {
            // Show preview table
            const table = document.getElementById("previewTable");
            table.innerHTML = "";

            // Header
            
            let headerRow = "<tr>";
            data.header.forEach(h => { headerRow += `<th>${h}</th>`; });
            headerRow += "</tr>";
            table.innerHTML += headerRow;

            // Rows
            data.rows.forEach(row => {
                let tr = "<tr>";
                data.header.forEach(h => { tr += `<td>${row[h]}</td>`; });
                tr += "</tr>";
                table.innerHTML += tr;
            });


            document.getElementById('btn-preview').hidden = true;
            document.getElementById('choose_file').hidden = true;
            document.getElementById('staticBackdrop').style.width = "800px"
            document.getElementById('staticBackdrop').style.left = "30%"
            document.getElementById('staticBackdrop').style.top = "5%"
            document.getElementById('confirmImport').hidden = false;
            document.getElementById('btn-reset').hidden = false;

            document.getElementById("previewArea").style.display = "block";
            window.csvRows = data.rows; // store for later import

            hideSpinner();
                return;
        }else {
            document.getElementById("importResult").innerHTML = data.message

            hideSpinner();
        }
        
    })
    .catch(err => { console.error("Preview failed:", err); hideSpinner();});
});
  
}

document.getElementById('btn-reset').hidden = true;
document.getElementById('confirmImport').hidden = true;

loadStudents();


    //Delete (Soft Delete) student
function deleteStudent(e) {
    if (!confirm("Are you sure you want to delete this student?")) return;
 
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);

    showSpinner();

    fetch("/api/students/delete?apiKey=devKey123", {
        method: 'POST',
        body: formData
        /* headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id: id}) */
    })
    .then(res => res.json())
    .then(data => {
    
        hideSpinner();
        if (data.success) {
            alert(data.message);
            loadStudents(); // reload student list
        } else {
            alert(data.message);

            console.log(data);
            
        }
    })
    .catch(err => {
        hideSpinner();
        console.log(err);
        alert("An error occurred while deleting. " . err);
    });
}

</script>