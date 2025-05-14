<?php

function permissionsStringToPermissionDigits($permissions) {
    // Start zeroed
    $permissions_array = array_fill(0, 9, '0');

    // Loop through the user permissions
    $permissions_array = explode('; ', $permissions);
    foreach ($permissions_array as $permission) {
        switch ($permission) {
            // Full Access Permissions (Index 0)
            case '*':
                $permissions_array[0] = '1';
                break;

            // Articles Permissions (Index 1)
            case 'articles.*':
                $permissions_array[1] = '1';
                break;
            case 'articles.add':
                $permissions_array[1] = '2';
                break;
            case 'articles.modify':
                $permissions_array[1] = '3';
                break;
            case 'articles.remove':
                $permissions_array[1] = '4';
                break;
            case 'articles.add-modify':
                $permissions_array[1] = '5';
                break;
            case 'articles.add-remove':
                $permissions_array[1] = '6';
                break;
            case 'articles.remove-modify':
                $permissions_array[1] = '7';
                break;

            // Articles Categories Permissions (Index 2)
            case 'articles-cat.*':
                $permissions_array[2] = '1';
                break;
            case 'articles-cat.add':
                $permissions_array[2] = '2';
                break;
            case 'articles-cat.modify':
                $permissions_array[2] = '3';
                break;
            case 'articles-cat.remove':
                $permissions_array[2] = '4';
                break;
            case 'articles-cat.add-modify':
                $permissions_array[2] = '5';
                break;
            case 'articles-cat.add-remove':
                $permissions_array[2] = '6';
                break;
            case 'articles-cat.remove-modify':
                $permissions_array[2] = '7';
                break;

            // Articles Subcategories Permissions (Index 3)
            case 'articles-subcat.*':
                $permissions_array[3] = '1';
                break;
            case 'articles-subcat.add':
                $permissions_array[3] = '2';
                break;
            case 'articles-subcat.modify':
                $permissions_array[3] = '3';
                break;
            case 'articles-subcat.remove':
                $permissions_array[3] = '4';
                break;
            case 'articles-subcat.add-modify':
                $permissions_array[3] = '5';
                break;
            case 'articles-subcat.add-remove':
                $permissions_array[3] = '6';
                break;
            case 'articles-subcat.remove-modify':
                $permissions_array[3] = '7';
                break;

            // Wiki Permissions (Index 4)
            case 'wiki.*':
                $permissions_array[4] = '1';
                break;
            case 'wiki.add':
                $permissions_array[4] = '2';
                break;
            case 'wiki.modify':
                $permissions_array[4] = '3';
                break;
            case 'wiki.remove':
                $permissions_array[4] = '4';
                break;
            case 'wiki.add-modify':
                $permissions_array[4] = '5';
                break;
            case 'wiki.add-remove':
                $permissions_array[4] = '6';
                break;
            case 'wiki.remove-modify':
                $permissions_array[4] = '7';
                break;

            // Wiki Categories Permissions (Index 5)
            case 'wiki-cat.*':
                $permissions_array[5] = '1';
                break;
            case 'wiki-cat.add':
                $permissions_array[5] = '2';
                break;
            case 'wiki-cat.modify':
                $permissions_array[5] = '3';
                break;
            case 'wiki-cat.remove':
                $permissions_array[5] = '4';
                break;
            case 'wiki-cat.add-modify':
                $permissions_array[5] = '5';
                break;
            case 'wiki-cat.add-remove':
                $permissions_array[5] = '6';
                break;
            case 'wiki-cat.remove-modify':
                $permissions_array[5] = '7';
                break;

            // Wiki Subcategories Permissions (Index 6)
            case 'wiki-subcat.*':
                $permissions_array[6] = '1';
                break;
            case 'wiki-subcat.add':
                $permissions_array[6] = '2';
                break;
            case 'wiki-subcat.modify':
                $permissions_array[6] = '3';
                break;
            case 'wiki-subcat.remove':
                $permissions_array[6] = '4';
                break;
            case 'wiki-subcat.add-modify':
                $permissions_array[6] = '5';
                break;
            case 'wiki-subcat.add-remove':
                $permissions_array[6] = '6';
                break;
            case 'wiki-subcat.remove-modify':
                $permissions_array[6] = '7';
                break;

            // Profiles Permissions (Index 7)
            case 'profiles.*':
                $permissions_array[7] = '1';
                break;
            case 'profiles.add':
                $permissions_array[7] = '2';
                break;
            case 'profiles.modify':
                $permissions_array[7] = '3';
                break;
            case 'profiles.remove':
                $permissions_array[7] = '4';
                break;
            case 'profiles.add-modify':
                $permissions_array[7] = '5';
                break;
            case 'profiles.add-remove':
                $permissions_array[7] = '6';
                break;
            case 'profiles.remove-modify':
                $permissions_array[7] = '7';
                break;

            // Profile Restriction Permissions (Index 8)
            case 'all-profiles':
                $permissions_array[8] = '1';
                break;
            case 'your-profile':
                $permissions_array[8] = '2';
                break;
        }
    }

    // Convert the array into a string of digits and return
    return implode('', $permissions_array);
}


function permissionInPermissionDigits($digitsString, $query_permission) {
    // string => [<digit>]
    $perm_array = str_split($digitsString['perm']);

    // If index 0='1' => full access
    if (isset($perm_array[0]) && $perm_array[0] === '1') {
        return true;
    }

    // Iterate through the permissions array and check each index
    foreach ($perm_array as $index => $permission) {
        // Check each permission index for the required permission
        switch ($index) {
            case 0:
                // Checked above
                break;
            case 1:
                if ($query_permission === 'articles.*' && $permission !== '0') {
                    return true;
                }
                if ($query_permission === 'articles.add' && $permission === '2') {
                    return true;
                }
                if ($query_permission === 'articles.modify' && $permission === '3') {
                    return true;
                }
                if ($query_permission === 'articles.remove' && $permission === '4') {
                    return true;
                }
                if ($query_permission === 'articles.add-modify' && $permission === '5') {
                    return true;
                }
                if ($query_permission === 'articles.add-remove' && $permission === '6') {
                    return true;
                }
                if ($query_permission === 'articles.remove-modify' && $permission === '7') {
                    return true;
                }
                break;
            case 2:
                if ($query_permission === 'articles-cat.*' && $permission !== '0') {
                    return true;
                }
                if ($query_permission === 'articles-cat.add' && $permission === '2') {
                    return true;
                }
                if ($query_permission === 'articles-cat.modify' && $permission === '3') {
                    return true;
                }
                if ($query_permission === 'articles-cat.remove' && $permission === '4') {
                    return true;
                }
                if ($query_permission === 'articles-cat.add-modify' && $permission === '5') {
                    return true;
                }
                if ($query_permission === 'articles-cat.add-remove' && $permission === '6') {
                    return true;
                }
                if ($query_permission === 'articles-cat.remove-modify' && $permission === '7') {
                    return true;
                }
                break;
            case 3:
                if ($query_permission === 'articles-subcat.*' && $permission !== '0') {
                    return true;
                }
                if ($query_permission === 'articles-subcat.add' && $permission === '2') {
                    return true;
                }
                if ($query_permission === 'articles-subcat.modify' && $permission === '3') {
                    return true;
                }
                if ($query_permission === 'articles-subcat.remove' && $permission === '4') {
                    return true;
                }
                if ($query_permission === 'articles-subcat.add-modify' && $permission === '5') {
                    return true;
                }
                if ($query_permission === 'articles-subcat.add-remove' && $permission === '6') {
                    return true;
                }
                if ($query_permission === 'articles-subcat.remove-modify' && $permission === '7') {
                    return true;
                }
                break;
            case 4:
                if ($query_permission === 'wiki.*' && $permission !== '0') {
                    return true;
                }
                if ($query_permission === 'wiki.add' && $permission === '2') {
                    return true;
                }
                if ($query_permission === 'wiki.modify' && $permission === '3') {
                    return true;
                }
                if ($query_permission === 'wiki.remove' && $permission === '4') {
                    return true;
                }
                if ($query_permission === 'wiki.add-modify' && $permission === '5') {
                    return true;
                }
                if ($query_permission === 'wiki.add-remove' && $permission === '6') {
                    return true;
                }
                if ($query_permission === 'wiki.remove-modify' && $permission === '7') {
                    return true;
                }
                break;
            case 5:
                if ($query_permission === 'wiki-cat.*' && $permission !== '0') {
                    return true;
                }
                if ($query_permission === 'wiki-cat.add' && $permission === '2') {
                    return true;
                }
                if ($query_permission === 'wiki-cat.modify' && $permission === '3') {
                    return true;
                }
                if ($query_permission === 'wiki-cat.remove' && $permission === '4') {
                    return true;
                }
                if ($query_permission === 'wiki-cat.add-modify' && $permission === '5') {
                    return true;
                }
                if ($query_permission === 'wiki-cat.add-remove' && $permission === '6') {
                    return true;
                }
                if ($query_permission === 'wiki-cat.remove-modify' && $permission === '7') {
                    return true;
                }
                break;
            case 6:
                if ($query_permission === 'wiki-subcat.*' && $permission !== '0') {
                    return true;
                }
                if ($query_permission === 'wiki-subcat.add' && $permission === '2') {
                    return true;
                }
                if ($query_permission === 'wiki-subcat.modify' && $permission === '3') {
                    return true;
                }
                if ($query_permission === 'wiki-subcat.remove' && $permission === '4') {
                    return true;
                }
                if ($query_permission === 'wiki-subcat.add-modify' && $permission === '5') {
                    return true;
                }
                if ($query_permission === 'wiki-subcat.add-remove' && $permission === '6') {
                    return true;
                }
                if ($query_permission === 'wiki-subcat.remove-modify' && $permission === '7') {
                    return true;
                }
                break;
            case 7:
                if ($query_permission === 'profiles.*' && $permission !== '0') {
                    return true;
                }
                if ($query_permission === 'profiles.add' && $permission === '2') {
                    return true;
                }
                if ($query_permission === 'profiles.modify' && $permission === '3') {
                    return true;
                }
                if ($query_permission === 'profiles.remove' && $permission === '4') {
                    return true;
                }
                if ($query_permission === 'profiles.add-modify' && $permission === '5') {
                    return true;
                }
                if ($query_permission === 'profiles.add-remove' && $permission === '6') {
                    return true;
                }
                if ($query_permission === 'profiles.remove-modify' && $permission === '7') {
                    return true;
                }
                break;
            case 8:
                if ($query_permission === 'all-profiles' && $permission === '1') {
                    return true;
                }
                if ($query_permission === 'your-profile' && $permission === '2') {
                    return true;
                }
                break;
        }
    }

    return false;
}