<?php
function generate_custom_alert($message, $type = 'primary') {
    $valid_types = ['primary', 'success', 'danger'];
    $type = in_array($type, $valid_types) ? $type : 'primary';

    $alert_class = 'custom-alert-' . $type;
    return "
    <div class='custom-alert $alert_class' role='alert'>
        <h5>" . htmlspecialchars($message) . "</h5>
        <span class='custom-alert-close'>x</span>
    </div>
    ";
}
?>