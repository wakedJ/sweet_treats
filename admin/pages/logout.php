<div class="card">
    <div class="card-header">
        <h2>Logout Confirmation</h2>
    </div>
    <div class="card-body">
        <div class="section-message">
            <p>Are you sure you want to logout from SweetTreats Admin Panel?</p>
        </div>
        
        <div class="action-buttons">
            <form method="POST" action="backend/admin_logout_process.php">
                <a href="../index.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" name="confirm_logout" value="yes" class="btn btn-primary">Logout</button>
            </form>
        </div>
    </div>
</div>

<style>
    .card {
        max-width: 500px;
        margin: 50px auto;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        border-radius: 8px;
        overflow: hidden;
    }
    
    .card-header {
        background-color: #FF69B4;
        color: white;
        padding: 15px 20px;
    }
    
    .card-header h2 {
        margin: 0;
        font-size: 1.5rem;
    }
    
    .card-body {
        padding: 30px 20px;
        text-align: center;
    }
    
    .section-message {
        margin-bottom: 25px;
        font-size: 1.1rem;
    }
    
    .action-buttons {
        display: flex;
        justify-content: center;
        gap: 15px;
    }
    
    .action-buttons form {
        display: flex;
        gap: 15px;
    }
    
    .btn {
        padding: 10px 20px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1rem;
        text-decoration: none;
        display: inline-block;
    }
    
    .btn-primary {
        background-color: #FF69B4;
        color: white;
        border: none;
    }
    
    .btn-secondary {
        background-color: #6c757d;
        color: white;
        border: none;
    }
</style>