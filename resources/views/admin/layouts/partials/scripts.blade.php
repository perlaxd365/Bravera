@vite('resources/js/admin.js')
<script>
    document.addEventListener('livewire:init', () => {

        Livewire.on('notify', (event) => {

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: event.type,
                title: event.message,
                showConfirmButton: false,
                timer: 2500
            });

        });

    });
</script>
