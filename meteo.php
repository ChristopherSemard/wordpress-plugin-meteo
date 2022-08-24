<?php
/*
Plugin Name: Météo Nantes
Plugin URI: https://mon-siteweb.com/
Description: Ceci est mon premier plugin
Author: Mon nom et prénom ou celui de ma société
Version: 1.0
Author URI: http://mon-siteweb.com/
*/

!get_option('city') ? add_option('city', 'Paris') : '';
!get_option('unit') ? add_option('unit', 'metric') : '';

function getDataMeteo()
{
    $city = get_option('city');
    $unit = get_option('unit');
    $api_url = 'https://api.openweathermap.org/data/2.5/weather?q=' . $city . '&units=' . $unit . '&lang=fr&appid=92c3fd34ea87fe572aaad5a6f99029fb';

    //set your own error handler before the call
    set_error_handler(function ($err_severity, $err_msg, $err_file, $err_line, array $err_context) {
        throw new ErrorException($err_msg, 0, $err_severity, $err_file, $err_line);
    }, E_WARNING);

    try {
        // Read JSON file
        $json_data = file_get_contents($api_url);
        // Decode JSON data into PHP array
        $response_data = json_decode($json_data);
        return $response_data;
    } catch (Exception $e) {
        return false;
    }

    //restore the previous error handler
    restore_error_handler();
}
function displayMeteo()
{
    $data = getDataMeteo();
    if ($data) {
        $unit = get_option('unit') == 'metric' ? '°C' : (get_option('unit') == 'imperial' ? '°F' : 'K');
        echo ('<div class="weather-box"><img src="https://openweathermap.org/img/wn/' . $data->weather[0]->icon . '.png"/><div class="weather-box-infos"><h2>' . $data->name . '</h2><p>' . $data->main->temp . $unit . ' -  ' . $data->weather[0]->description . '</p></div></div>');
    } else {
        echo ('<p id="weather-warning" style="color:red;" >WARNING : Impossible d\'afficher les informations de météo car la ville spécifiée n\'existe pas, merci de la modifier dans les paramètres du plugins.</p>');
    }
}

// Now we set that function up to execute when the admin_notices action is called.
add_action('admin_notices', 'displayMeteo');


// ADMINISTRATION PAGE

function settings_page()
{
    add_options_page('Météo', 'Météo', 'manage_options', 'dbi-example-plugin', 'render_page');
}
add_action('admin_menu', 'settings_page');

function render_page()
{
?>
    <div class="wrap">
        <h1>Paramètres du plugin Météo</h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('my-options');
            do_settings_sections('my-options');
            ?>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Ville</th>
                    <td>
                        <!-- <input type="text" name="city" value="<?php echo esc_attr(get_option('city')); ?>" />   -->
                        <input type="text" id="inputCity" class="cityAutocomplete form-control" name='city' value="<?php echo esc_attr(get_option('city')); ?>">
                        <ul class="list list-group"></ul>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Unité</th>
                    <td>
                        <select name="unit" id="inputUnit">
                            <option value="metric" <?= get_option('unit') === 'metric' ? 'selected' : '' ?>>Celcius</option>
                            <option value="imperial" <?= get_option('unit') === 'imperial' ? 'selected' : '' ?>>Fahrenheit</option>
                            <option value="standard" <?= get_option('unit') === 'standard' ? 'selected' : '' ?>>Kelvin</option>
                        </select>
                    </td>
                </tr>
            </table>
            <?php submit_button();

            ?>
        </form>
    </div>

<?php
}


function register_mysettings()
{ // whitelist options
    register_setting('my-options', 'city');
    register_setting('my-options', 'unit');
}
add_action('admin_init', 'register_mysettings');

function add_style()
{
    wp_register_style('meteo_css', plugins_url('/css/meteo.css', __FILE__));
    wp_enqueue_style('meteo_css');
    wp_register_script('meteo_js', plugins_url('/js/meteo.js', __FILE__));
    wp_enqueue_script('meteo_js');
}
add_action('admin_init', 'add_style');

function add_defer_attribute($tag, $handle)
{
    if ('meteo_js' !== $handle)
        return $tag;
    return str_replace(' src', ' defer="defer" src', $tag);
}

add_filter('script_loader_tag', 'add_defer_attribute', 10, 2);
