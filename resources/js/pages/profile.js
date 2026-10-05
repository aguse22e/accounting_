// Profile specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('profileForm');
    const btn = document.getElementById('saveProfileBtn');
    
    if (form && btn) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const fd = new FormData(form);
            btn.disabled = true;
            const old = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan';
            
            try {
                fd.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                const res = await fetch(form.action, { 
                    method: "POST", 
                    body: fd, 
                    credentials: "same-origin" 
                });
                
                if (res.ok) {
                    // Show success message
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-success alert-dismissible fade show';
                    alert.innerHTML = `
                        Profil berhasil diperbarui
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    form.insertBefore(alert, form.firstChild);
                    
                    // Remove alert after 3 seconds
                    setTimeout(() => {
                        alert.remove();
                    }, 3000);
                } else {
                    throw new Error('Gagal menyimpan profil');
                }
            } catch(e) {
                alert('Gagal menyimpan: ' + e.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = old;
            }
        });
    }
    
    console.log('Profile page loaded');
});

