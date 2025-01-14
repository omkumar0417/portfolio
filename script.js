document.getElementById('theme-toggle').addEventListener('click', function() {
    document.body.classList.toggle('dark-mode');
    document.querySelector('header').classList.toggle('dark-mode');
    document.querySelectorAll('section').forEach(section => section.classList.toggle('dark-mode'));
    document.querySelectorAll('form input, form textarea').forEach(input => input.classList.toggle('dark-mode'));
    document.querySelectorAll('h1').forEach(h1 => h1.classList.toggle('dark-mode'));
    document.querySelectorAll('.project p').forEach(p => p.classList.toggle('dark-mode'));
    this.textContent = document.body.classList.contains('dark-mode') ? 'Switch to Light Mode' : 'Switch to Dark Mode';
});

document.getElementById('contact-form').addEventListener('submit', function(event) {
    event.preventDefault();
    emailjs.sendForm('service_cqggltu', 'template_2rol4ff', this)
        .then(function() {
            alert('Message sent successfully!');
        }, function(error) {
            alert('Failed to send message: ' + JSON.stringify(error));
        });
});
