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
$processes = $response[SHINA_IMPORT_PLUGIN_NAME];

if (empty($processes)) {
    wp_die('No DB Table', 'No DB Table');
}
?>

<div class="container" id="shina_import_container">
    <h1>Обработка файлов</h1>
    <br />
    <div class="accordion" id="accordionExample">
        <?php foreach ($processes as $process) { ?>
            <div class="card">
                <div class="card-header" id="heading<?= $process['id'] ?>">
                    <h2 class="mb-0">
                        <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapse<?= $process['id'] ?>" aria-expanded="true" aria-controls="collapse<?= $process['id'] ?>">
                            Обработка файла <code><?= $process['file_name'] ?></code>
                        </button>
                    </h2>
                    <div id="import_progress_bar_title_<?= $process['id'] ?>" class="progress-bar progress-bar-striped progress-bar-animated active" role="progressbar" aria-valuemin="0"
                         aria-valuemax="100" style="<?= $process['progress_bar_style']; ?>">
                    </div>
                </div>

                <div id="collapse<?= $process['id'] ?>" class="collapse show" aria-labelledby="headingOne" data-parent="#accordionExample">
                    <div class="card-body">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                            </div>
                            <div class="panel-body">
                            <span id="import_message_<?= $process['id'] ?>">
                                <?= $process['message']; ?>
                            </span>
                                <?php if ($process['status'] !== 'error') { ?>
                                    <div class="form-group row">
                                        <div class="col-md-6 control-label">Последнее обновление файла <?= $process['file_name'] ?></div>
                                        <div class="col-md-6 control-label"><?= $process['file_mod_time']; ?></div>
                                    </div>
                                <?php } ?>
                                <div class="form-group" id="import_process_<?= $process['id'] ?>" style="<?= $process['progress_style']; ?>">
                                    <div class="progress">
                                        <div id="import_progress_bar_<?= $process['id'] ?>" class="progress-bar progress-bar-striped progress-bar-animated active" role="progressbar" aria-valuemin="0"
                                             aria-valuemax="100" style="<?= $process['progress_bar_style']; ?>">
                                            <span id="import_process_data_<?= $process['id'] ?>"><?= $process['percent']; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
</div>
<!-- This file should primarily consist of HTML with a little bit of PHP. -->
