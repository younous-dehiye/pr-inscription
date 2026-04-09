document.addEventListener('DOMContentLoaded', function() {
    // Gestion du choix de la faculté/école
    const faculteSelect = document.getElementById('faculte');
    const departementSelect = document.getElementById('departement');
    const filiereSelect = document.getElementById('filiere');
    
    if (faculteSelect) {
        faculteSelect.addEventListener('change', function() {
            const faculteId = this.value;
            if (faculteId) {
                fetchDepartements(faculteId);
            } else {
                resetSelects([departementSelect, filiereSelect]);
            }
        });
    }
    
    if (departementSelect) {
        departementSelect.addEventListener('change', function() {
            const departementId = this.value;
            if (departementId) {
                fetchFilieres(departementId);
            } else {
                resetSelects([filiereSelect]);
            }
        });
    }
    
    // Validation du formulaire
    const inscriptionForm = document.getElementById('inscriptionForm');
    if (inscriptionForm) {
        inscriptionForm.addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
            }
        });
    }
    
    // Gestion du mode de paiement
    const modePaiement = document.getElementById('mode_paiement');
    const paiementInfo = document.getElementById('paiement_info');
    
    if (modePaiement) {
        modePaiement.addEventListener('change', function() {
            showPaiementInfo(this.value);
        });
    }
});

function fetchDepartements(faculteId) {
    fetch(`get_departements.php?faculte_id=${faculteId}`)
        .then(response => response.json())
        .then(data => {
            const departementSelect = document.getElementById('departement');
            departementSelect.innerHTML = '<option value="">Sélectionnez un département</option>';
            data.forEach(dept => {
                departementSelect.innerHTML += `<option value="${dept.id}">${dept.nom}</option>`;
            });
            departementSelect.disabled = false;
            
            // Reset filière
            const filiereSelect = document.getElementById('filiere');
            filiereSelect.innerHTML = '<option value="">Sélectionnez d\'abord un département</option>';
            filiereSelect.disabled = true;
        });
}

function fetchFilieres(departementId) {
    fetch(`get_filieres.php?departement_id=${departementId}`)
        .then(response => response.json())
        .then(data => {
            const filiereSelect = document.getElementById('filiere');
            filiereSelect.innerHTML = '<option value="">Sélectionnez une filière</option>';
            data.forEach(filiere => {
                filiereSelect.innerHTML += `<option value="${filiere.id}">${filiere.nom}</option>`;
            });
            filiereSelect.disabled = false;
        });
}

function resetSelects(selects) {
    selects.forEach(select => {
        if (select) {
            select.innerHTML = '<option value="">Sélectionnez d\'abord</option>';
            select.disabled = true;
        }
    });
}

function validateForm() {
    let isValid = true;
    const requiredFields = document.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.style.borderColor = 'red';
            isValid = false;
        } else {
            field.style.borderColor = '#e0e0e0';
        }
    });
    
    // Validation email
    const email = document.getElementById('email');
    if (email && email.value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email.value)) {
            email.style.borderColor = 'red';
            isValid = false;
        }
    }
    
    // Validation téléphone
    const telephone = document.getElementById('telephone');
    if (telephone && telephone.value) {
        const phoneRegex = /^[0-9]{9}$/;
        if (!phoneRegex.test(telephone.value)) {
            telephone.style.borderColor = 'red';
            isValid = false;
        }
    }
    
    if (!isValid) {
        alert('Veuillez remplir tous les champs obligatoires correctement.');
    }
    
    return isValid;
}

function showPaiementInfo(mode) {
    const infoDiv = document.getElementById('paiement_info');
    if (mode === 'Express Union') {
        infoDiv.innerHTML = `
            <div class="alert alert-info">
                <strong>Instructions de paiement Express Union:</strong><br>
                - Rendez-vous dans n'importe quelle agence Express Union<br>
                - Compte: 1234567890<br>
                - Montant: 5000 FCFA<br>
                - Référence: UNV-${Date.now()}
            </div>
        `;
    } else if (mode === 'CCA Bank') {
        infoDiv.innerHTML = `
            <div class="alert alert-info">
                <strong>Instructions de paiement CCA Bank:</strong><br>
                - Rendez-vous dans n'importe quelle agence CCA Bank<br>
                - Compte: 9876543210<br>
                - Montant: 5000 FCFA<br>
                - Référence: CCA-${Date.now()}
            </div>
        `;
    }
}