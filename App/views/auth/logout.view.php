<script>
    
    fetch("/api/logout", {
        method: "get",
    })
    .then(res => res.json())
    .then(data => {console.log(data);
        if (data.success) {
            window.location.href = data.redirect;
        }
    })
    .catch(err => {
        console.error(err);
    });

  </script>