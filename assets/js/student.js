document.addEventListener('DOMContentLoaded', function() {
    // Initialize loan buttons
    document.querySelectorAll('.loan-btn:not(.disabled)').forEach(button => {
        button.addEventListener('click', function() {
            const bookId = this.getAttribute('data-book-id');
            if (!confirm('Are you sure you want to request this book?')) return;
            
            fetch('includes/request_loan.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `book_id=${bookId}`
            })
            .then(handleResponse)
            .catch(handleError);
        });
    });

    // Initialize return buttons
    document.querySelectorAll('.return-btn').forEach(button => {
        button.addEventListener('click', function() {
            const loanId = this.getAttribute('data-loan-id');
            if (!confirm('Are you sure you want to return this book?')) return;
            
            fetch('includes/return_book.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `loan_id=${loanId}`
            })
            .then(handleResponse)
            .catch(handleError);
        });
    });

    // Handle response
    function handleResponse(response) {
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                throw new Error(`Invalid response: ${text.substring(0, 100)}`);
            });
        }
        return response.json().then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                throw new Error(data.message);
            }
        });
    }

    // Handle errors
    function handleError(error) {
        console.error('Error:', error);
        alert('Operation failed: ' + error.message);
    }
});