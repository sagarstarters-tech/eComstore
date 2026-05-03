<?php
function get_country_codes() {
    return [
        '+91' => 'India (+91)',
        '+1' => 'USA/Canada (+1)',
        '+44' => 'UK (+44)',
        '+61' => 'Australia (+61)',
        '+81' => 'Japan (+81)',
        '+86' => 'China (+86)',
        '+49' => 'Germany (+49)',
        '+33' => 'France (+33)',
        '+7' => 'Russia (+7)',
        '+55' => 'Brazil (+55)',
        '+27' => 'South Africa (+27)',
        '+971' => 'UAE (+971)',
        '+966' => 'Saudi Arabia (+966)',
        '+65' => 'Singapore (+65)',
        '+60' => 'Malaysia (+60)',
        '+62' => 'Indonesia (+62)',
        '+66' => 'Thailand (+66)',
        '+84' => 'Vietnam (+84)',
        '+63' => 'Philippines (+63)',
        '+82' => 'South Korea (+82)',
        '+90' => 'Turkey (+90)',
        '+39' => 'Italy (+39)',
        '+34' => 'Spain (+34)',
        '+31' => 'Netherlands (+31)',
        '+41' => 'Switzerland (+41)',
        '+46' => 'Sweden (+46)',
        '+47' => 'Norway (+47)',
        '+45' => 'Denmark (+45)',
        '+358' => 'Finland (+358)',
        '+353' => 'Ireland (+353)',
        '+32' => 'Belgium (+32)',
        '+43' => 'Austria (+43)',
        '+30' => 'Greece (+30)',
        '+351' => 'Portugal (+351)',
        '+48' => 'Poland (+48)',
        '+420' => 'Czech Republic (+420)',
        '+36' => 'Hungary (+36)',
        '+40' => 'Romania (+40)',
        '+380' => 'Ukraine (+380)',
        '+20' => 'Egypt (+20)',
        '+234' => 'Nigeria (+234)',
        '+254' => 'Kenya (+254)',
        '+212' => 'Morocco (+212)',
        '+213' => 'Algeria (+213)',
        '+216' => 'Tunisia (+216)',
        '+92' => 'Pakistan (+92)',
        '+880' => 'Bangladesh (+880)',
        '+94' => 'Sri Lanka (+94)',
        '+977' => 'Nepal (+977)',
        '+95' => 'Myanmar (+95)',
        '+855' => 'Cambodia (+855)',
        '+856' => 'Laos (+856)',
        '+93' => 'Afghanistan (+93)',
        '+964' => 'Iraq (+964)',
        '+98' => 'Iran (+98)',
        '+963' => 'Syria (+963)',
        '+962' => 'Jordan (+962)',
        '+961' => 'Lebanon (+961)',
        '+972' => 'Israel (+972)',
        '+965' => 'Kuwait (+965)',
        '+974' => 'Qatar (+974)',
        '+973' => 'Bahrain (+973)',
        '+968' => 'Oman (+968)',
        '+967' => 'Yemen (+967)',
        '+52' => 'Mexico (+52)',
        '+54' => 'Argentina (+54)',
        '+56' => 'Chile (+56)',
        '+57' => 'Colombia (+57)',
        '+51' => 'Peru (+51)',
        '+58' => 'Venezuela (+58)',
        '+593' => 'Ecuador (+593)',
        '+591' => 'Bolivia (+591)',
        '+595' => 'Paraguay (+595)',
        '+598' => 'Uruguay (+598)',
        '+64' => 'New Zealand (+64)',
        '+679' => 'Fiji (+679)',
    ];
}

function render_phone_input($name, $value = '', $required = true, $class = '', $id_class = '') {
    $codes = get_country_codes();
    $selected_code = '+91'; // Default
    $phone_body = $value;

    // Try to extract country code from value if it exists
    foreach ($codes as $code => $label) {
        if (!empty($value) && strpos($value, $code) === 0) {
            $selected_code = $code;
            $phone_body = substr($value, strlen($code));
            break;
        }
    }

    // Self-contained styling for the phone group
    static $css_added = false;
    ob_start();
    if (!$css_added) {
        $css_added = true;
        ?>
        <style>
            .phone-group {
                display: flex !important;
                align-items: stretch !important;
                border: 1px solid #ced4da !important;
                border-radius: 8px !important;
                overflow: hidden;
                background-color: #fff !important;
                transition: border-color 0.2s ease, box-shadow 0.2s ease;
            }
            .phone-group .country-code-select {
                width: 80px !important;
                flex: 0 0 80px !important;
                background-color: #f8f9fa !important;
                border: none !important;
                border-right: 1px solid #ced4da !important;
                border-radius: 0 !important;
                font-size: 0.9rem !important;
                padding: 0 22px 0 10px !important;
                height: 44px !important; 
                line-height: 44px !important;
                cursor: pointer;
                -webkit-appearance: none;
                -moz-appearance: none;
                appearance: none;
                background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
                background-repeat: no-repeat;
                background-position: right 6px center;
                background-size: 10px 10px;
                color: #444 !important;
                margin: 0 !important;
                box-shadow: none !important;
            }
            .phone-group .phone-main-input {
                border: none !important;
                border-radius: 0 !important;
                height: 44px !important;
                line-height: normal !important;
                font-size: 1rem !important;
                padding: 0 15px !important;
                margin: 0 !important;
                flex: 1;
                box-shadow: none !important;
            }
            .phone-group:focus-within {
                border-color: #86b7fe !important;
                box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15) !important;
            }
            /* Large Input Variant */
            .phone-group.form-control-lg {
                border-radius: 10px !important;
            }
            .phone-group.form-control-lg .country-code-select {
                height: 52px !important;
                line-height: 52px !important;
                width: 90px !important;
                flex: 0 0 90px !important;
                font-size: 1rem !important;
            }
            .phone-group.form-control-lg .phone-main-input {
                height: 52px !important;
                font-size: 1.1rem !important;
                padding: 0 18px !important;
            }
        </style>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            function updatePhone(group) {
                const code = group.querySelector('.country-code-select').value;
                const phone = group.querySelector('.phone-main-input').value;
                group.querySelector('.phone-hidden-final').value = code + ' ' + phone.trim();
            }
            
            document.addEventListener('input', function(e) {
                const group = e.target.closest('.phone-group');
                if (group && (e.target.classList.contains('phone-main-input') || e.target.classList.contains('country-code-select'))) {
                    updatePhone(group);
                }
            });
            
            document.addEventListener('change', function(e) {
                const group = e.target.closest('.phone-group');
                if (group && e.target.classList.contains('country-code-select')) {
                    updatePhone(group);
                }
            });
        });
        </script>
        <?php
        $css_added = true;
    }
    ?>
    <div class="phone-group <?php echo $class; ?> <?php echo $id_class; ?>">
        <select class="form-select country-code-select" aria-label="Country Code">
            <?php foreach ($codes as $code => $label): ?>
                <option value="<?php echo $code; ?>" <?php echo $selected_code === $code ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($code); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="tel" 
               class="form-control phone-main-input" 
               value="<?php echo htmlspecialchars($phone_body); ?>" 
               placeholder="Phone Number"
               autocomplete="tel"
               <?php echo $required ? 'required' : ''; ?>>
        <input type="hidden" name="<?php echo $name; ?>" class="phone-hidden-final" value="<?php echo htmlspecialchars($value); ?>">
    </div>
    <?php
    return ob_get_clean();
}
?>
