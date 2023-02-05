<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://github.com/vovaborisenko
 * @since      1.0.0
 *
 * @package    Shina_Import
 * @subpackage Shina_Import/admin/partials
 */

$plugin_admin = new Shina_Import_Admin(SHINA_IMPORT_PLUGIN_NAME, SHINA_IMPORT_VERSION);
$response = $plugin_admin->heartbeat_send([]);
$process_import = $response[SHINA_IMPORT_PLUGIN_NAME];

if (empty($process_import)) {
    wp_die('No DB Table', 'No DB Table');
}
?>

<div class="container" id="shina_import_container">
    <h1>Обработка файла <code><?= SHINA_IMPORT_FILE_NAME ?></code></h1>
    <br />
    <div class="accordion" id="accordionExample">
        <div class="card">
            <div class="card-header" id="headingOne">
                <h2 class="mb-0">
                    <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                        Обработка файла
                    </button>
                </h2>
                <div id="import_progress_bar_title" class="progress-bar progress-bar-striped progress-bar-animated active" role="progressbar" aria-valuemin="0"
                     aria-valuemax="100" style="<?= $process_import['progress_bar_style']; ?>">
                </div>
            </div>

            <div id="collapseOne" class="collapse show" aria-labelledby="headingOne" data-parent="#accordionExample">
                <div class="card-body">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                        </div>
                        <div class="panel-body">
                            <span id="import_message">
                                <?= $process_import['message']; ?>
                            </span>
                            <?php if ($process_import['status'] !== 'error') { ?>
                                <div class="form-group row">
                                    <div class="col-md-6 control-label">Последнее обновление файла <?= SHINA_IMPORT_FILE_NAME; ?></div>
                                    <div class="col-md-6 control-label"><?= $process_import['file_mod_time']; ?></div>
                                </div>
                            <?php } ?>
                            <div class="form-group" id="import_process" style="<?= $process_import['progress_style']; ?>">
                                <div class="progress">
                                    <div id="import_progress_bar" class="progress-bar progress-bar-striped progress-bar-animated active" role="progressbar" aria-valuemin="0"
                                         aria-valuemax="100" style="<?= $process_import['progress_bar_style']; ?>">
                                        <span id="import_process_data"><?= $process_import['percent']; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- This file should primarily consist of HTML with a little bit of PHP. -->
