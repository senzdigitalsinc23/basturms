<style>
    body {
        background-color: rgba(251, 177, 177, 1);
    }
    .error-page {
        width: 100%;
        height: calc(100vh - 50px);
        display: flex;
        justify-content: center;
        align-items: center;
        text-align: center;
        width: 100%;
        padding: 0;
    }

    .error-main {
        width: fit-content;
        height: fit-content;
        background-color: red;
        padding: 50px;
        border-radius: 20px;
        background-color: rgba(253, 77, 77, 1);
        box-shadow: inset 5px 5px 5px rgba(0, 0, 0, 0.5);
    }
    .title {
        color: rgba(250, 215, 139, 1);
    }
    .message {
        color: rgba(247, 173, 12, 1);
    }
</style>
<div class="error-page">
    <div class="error-main">
        <h1 class="title"><?=$title?></h1>
        <h2 class="message"><?=$code. ": " . $message?></h3>
    </div>
    
</div>