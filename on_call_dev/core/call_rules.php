<?php
// Compute call_group and ageout_date based on your rules
function compute_call_group_and_ageout($role, $provider_type, $birthdate, $service_start_date) {
    $result = [
        'call_group'  => 'none',
        'ageout_date' => null
    ];

    // Normalize inputs
    $role           = $role ?? '';
    $provider_type  = $provider_type ?? '';
    $birthdate      = $birthdate ?: null;
    $service_start  = $service_start_date ?: null;

    // Admins: never on call
    if ($role === 'admin' || $provider_type === 'admin') {
        return $result;
    }

    // Non-employed surgeons: always ER call, no ageout
    if ($role === 'non_employed' && $provider_type === 'surgeon_md') {
        $result['call_group'] = 'non_employed_er';
        return $result;
    }

    // Need dates for age/service-based rules
    if (empty($birthdate) || empty($service_start)) {
        // Without both dates, we can't safely assign; keep them 'none'
        return $result;
    }

    $today = new DateTime('today');
    $dob   = new DateTime($birthdate);
    $start = new DateTime($service_start);

    $age     = $dob->diff($today)->y;
    $service = $start->diff($today)->y;

    // Employed surgeons
    if ($role === 'employed' && $provider_type === 'surgeon_md') {

        // Date when they turn 55
        $age55 = clone $dob;
        $age55->modify('+55 years');

        // Date when they hit 15 years of service
        $service15 = clone $start;
        $service15->modify('+15 years');

        // Backup begins at the LATER of those two dates
        $backup_start = ($age55 > $service15) ? $age55 : $service15;

        // Age-out is 5 years AFTER backup_start
        $backup_end = clone $backup_start;
        $backup_end->modify('+5 years');

        if ($today < $backup_start) {
            // Still full-call (ER)
            $result['call_group']  = 'luminis_er';
            $result['ageout_date'] = $backup_start->format('Y-m-d');
        }
        else if ($today >= $backup_start && $today < $backup_end) {
            // In the 5-year backup period
            $result['call_group']  = 'luminis_backup';
            $result['ageout_date'] = $backup_end->format('Y-m-d');
        }
        else {
            // Fully aged out after backup period
            $result['call_group']  = 'none';
            $result['ageout_date'] = $backup_end->format('Y-m-d');
        }

        return $result;
    }

    // Employed APPs / non-surgeon MDs
    if ($role === 'employed' && in_array($provider_type, ['app','non_surgeon_md'])) {
        if ($age >= 55 && $service >= 15) {
            // Aged out of practice call
            $result['call_group']  = 'none';
            $result['ageout_date'] = null;
        } else {
            // In practice call pool; age-out = earliest of turning 55 or 15 yrs service
            $age55     = clone $dob;
            $age55->modify('+55 years');
            $service15 = clone $start;
            $service15->modify('+15 years');

            $ageout = ($age55 > $service15) ? $age55 : $service15;

            $result['call_group']  = 'practice_call';
            $result['ageout_date'] = $ageout->format('Y-m-d');
        }
        return $result;
    }

    // Fallback: no call
    return $result;
}

