
<!--Alerts offcanvas-->
<div class="offcanvas offcanvas-end" data-bs-scroll="true" data-bs-backdrop="false" tabindex="-1" id="offcanvasScrolling" aria-labelledby="offcanvasScrollingLabel">
  <div class="offcanvas-body">
    <div id="alert-box" class="alert">
        <button id="btn-close" type="button" class="btn-close bg-white close-alert float-end"  data-bs-dismiss="offcanvas" aria-label="Close"></button>
        <div class="alert-message" id="alert-message"></div>
    </div>
  </div>
</div>

<div class="users-contanier">
    <!--Create User-->
    <div class="create-user" id="create-user">        
    <form method="post" id="register" class="needs-validation"  novalidate>
        <?=csrf_field()?>
        <h4 class="mb-1 mt-5"><?= isset($user) ? 'Edit User' : 'Add New User' ?></h4>
        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="name" id="name" class="form-control" value="<?=old('name') ?? '' ?>" required>
            
        </div>
        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" id="email" class="form-control" value="<?= old('email') ?? '' ?>" required>
        </div>
        <div class="mb-3">
            <label>Password <?= isset($user) ? '(Leave blank to keep current)' : '' ?></label>
            <input type="password" name="password" id="password" class="form-control" <?= isset($user) ? '' : 'required' ?>>
        </div>
        <div class="mb-3">
            <label>Role</label>
            <select name="role_id" id="role" class="form-select" required>
                <option value="">Select Role</option>
                <?php foreach ($roles as $role): ?>
                <option value="<?= $role['role_id'] ?>" <?= (isset($user) && $user['role_id'] == $role['id']) ? 'selected' : '' ?>>
                    <?= ucfirst($role['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-success btn-sm" id="save-user" data-bs-toggle="offcanvas" data-bs-target="#offcanvasScrolling" aria-controls="offcanvasScrolling">Save</button>
        <div class="btn btn-danger btn-sm" id="close-adduser"><?=icon('x-circle-fill')?></div>
                
    </form>
    </div>
        <!--List of users-->
    <div class="all-users">
    <div class="btn btn-primary btn-sm mb-3 mt-5 float-end" id="add-user"><?=icon('person-fill-add')?></div>
    <table class="table table-bordered">
        <thead>
            <tr><th>#</th><th>User ID</th><th>Name</th><th>Email</th><th>Role</th><th>Actions</th></tr>
        </thead>
            
            <tbody class="tbody" id="tbody">
                <tr id="trow"></tr>
                
            </tbody>
            
        </table>

        </div>
    </div>

</div>

<?php remove('_old')?>

<script>


    /* --- GET list of Users --- */
const loadBtn = document.getElementById('save-user');
const resultEl = document.getElementById('result');

const alertBox = document.getElementById("alert-box");
const alertMessage = document.getElementById('alert-message');
const btnClose = document.getElementById('btn-close');

document.getElementById('create-user').hidden = true;


//Function for creating new user
document.getElementById("register").addEventListener("submit", function(e) {

    (() => {
  'use strict'
  // Fetch all the forms we want to apply custom Bootstrap validation styles to
  const forms = document.querySelectorAll('.needs-validation')

  // Loop over them and prevent submission
  Array.from(forms).forEach(form => {
    form.addEventListener('submit', event => {
      if (!form.checkValidity()) {
        event.preventDefault()
        event.stopPropagation()
      }

      form.classList.add('was-validated')
    }, false)
  })
})();
 
        e.preventDefault();
        e.stopPropagation();

        time = 5000;

        const form = e.target;
        const formData = new FormData(form);

        fetch('/api/register?apiKey=devKey123', { 
            method  : 'POST',
            body    :   formData,
            credentials: 'same-origin'
        })
        .then(response => {        
            //if (!response.ok) throw new Error('Network response was not ok: ' + response.status);
            return response.json();
        })
        .then(data => {console.log(data);       

            if (data.success) {
                alertBox.classList.remove('alert-danger');
                alertBox.classList.add('alert-success');
                alertMessage.innerHTML = data.message
                alertBox.hidden = false;
                btnClose.hidden = false;

                loadUsers();
                resetForm();
                
            } else {           
                
                alertBox.classList.remove('alert-success');
                alertBox.classList.add('alert-danger');
                alertMessage.innerHTML = data.message

                btnClose.hidden = false;
                alertBox.hidden = false;            

            }

        /* setTimeout(() =>{
            btnClose.hidden = true;
            alertBox.hidden = true;
        }, time); */

    return;
    })
    .catch(err => {
        console.error('Fetch error:', err);
        //resultEl.textContent = 'Failed to load students.';
    });
}) 


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

function loadUsers() {
    let tbody = document.getElementById('tbody');
    let tableForm = '';
    fetch("/api/admin/users?apiKey=devKey123", {
        method: "get",
        credentials: 'same-origin'
    })
    .then(res => res.json())
    .then(data => {console.log(data.users.users);
    
        numbering = 1;
        if (data.success) {
            
            users = data.users.users;
            users.forEach((element) => {
                tableForm += `<tr>
                    <td>${numbering}</>
                    <td>${element.id}</td>
                    <td>${element.first_name} ${element.other_name} ${element.last_name}</td>
                    <td>${element.email}</td>
                    <td>${element.role_name}</td>
                    <td>
                        <a href="/web/admin/users/${element['id']}/edit" class="btn btn-sm btn-warning"><img src="/../assets/images/bootstrap-icons/pencil-square.svg" alt=""></a>
                        <a href="/web/admin/users/${element['id']}/delete" class="btn btn-sm btn-danger"><img src="/../assets/images/bootstrap-icons/unlock-fill.svg" alt=""></a>
                    </td>
                    </tr>`

                numbering++;
            })
                
        }
        tbody.innerHTML = tableForm;return;
    })
    .catch(err => {
        console.error(err);
    });
}

loadUsers();
    
const btnAddUser = document.getElementById('add-user');
const btnCloseAddUser = document.getElementById('close-adduser');

btnAddUser.addEventListener('click', () => {
    //alert()
    document.getElementById('create-user').hidden = false;
});

btnCloseAddUser.addEventListener('click', () => {
    //alert()
    document.getElementById('create-user').hidden = true;
})

btnClose.hidden = true;
alertBox.hidden = true;

btnClose.addEventListener('click', () => {
    btnClose.hidden = true;
    alertBox.hidden = true;
    alertMessage.innerHTML = ''
})
</script>
