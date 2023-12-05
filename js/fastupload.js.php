<?php
if (!defined('NOCSRFCHECK'))  define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);

require '../config.php';

$langs->load('fastupload@fastupload');

$max_file_size = 0;

// Dolibarr style @see html.formfile.php::form_attach_new_file
$max=getDolGlobalString('MAIN_UPLOAD_DOC');           // En Kb
$maxphpstr=@ini_get('upload_max_filesize')?:0; // En inconnu

// on recherche une série de chiffres (premier groupe de capture) suivi d'une lettre (optionnelle) parmi
// les suivantes : k, M, G, T
if (preg_match('/(\d+)([kMGT])?/', $maxphpstr, $m)) {
    $maxphp = intval($m[1]);
    $letter = strtolower($m[2]);
    if ($letter === 'k') $maxphp=$maxphp*1;
    if ($letter === 'm') $maxphp=$maxphp*1024;
    if ($letter === 'g') $maxphp=$maxphp*1024*1024;
    if ($letter === 't') $maxphp=$maxphp*1024*1024*1024;
    if ($letter === '') $maxphp = $maxphp;
}

// Now $max and $maxphp are in Kb
if ($maxphp > 0) $max=min($max,$maxphp);
if ($max > 0)
{
	$max_file_size = $max/1024; // Conversion Kb en Mb
}

$newToken = function_exists('newToken') ? newToken() : $_SESSION['newtoken'];

// Define javascript type
top_httphead('text/javascript; charset=UTF-8');
?>
$(document).ready( function() {
	Dropzone.autoDiscover = false;
	let enableDropzone = function(form, paramName) {
		var classPrefix = "dropzone";
		var zone_class = "." + classPrefix;
		var zone = $(zone_class);
		try {

			var zone_object = new Dropzone(form[0], {
				paramName: paramName,
				autoProcessQueue: <?php echo !empty(getDolGlobalString('FASTUPLOAD_ENABLE_AUTOUPLOAD')) ? 'true' : 'false'; ?>,
                    addRemoveLinks: !<?php echo !empty(getDolGlobalString('FASTUPLOAD_ENABLE_AUTOUPLOAD')) ? 'true' : 'false'; ?>,
				clickable: zone_class,
				previewsContainer: "#" + classPrefix + "-previews-box",
				uploadMultiple: <?php echo (float) DOL_VERSION < 4.0 ? 'false' : 'true'; ?>,
				parallelUploads: 100,
				previewTemplate: "<div class=\"dz-preview dz-file-preview\">\n  \n\
										<div class=\"dz-details\">\n\n\
											<div class=\"dz-filename\">\n\
												<span data-dz-name></span>\n\
											</div>\n\n\
											<div class=\"dz-size\" data-dz-size></div>\n\n\
											<img data-dz-thumbnail />\n\n\
										</div>\n\n\
										<div class=\"dz-progress\"><span class=\"dz-upload\" data-dz-uploadprogress></span></div>\n\
										<div class=\"dz-success-mark\"><span></span></div>\n\n\
										<div class=\"dz-error-mark\"><span></span></div>\n\n\
										<div class=\"dz-error-message\"><span data-dz-errormessage></span></div>\n\n\
									</div>",
				maxFilesize: <?php echo $max_file_size; ?>,
				maxFiles: <?php echo !empty(getDolGlobalInt('FASTUPLOAD_LIMIT_FILE_NUMBER')) ? getDolGlobalInt('FASTUPLOAD_LIMIT_FILE_NUMBER') : 50; ?>,
				dictDefaultMessage: "<?php echo addslashes($langs->transnoentities('FastUpload_DefaultMessage')); ?>",
				dictFallbackMessage: "<?php echo addslashes($langs->transnoentities('FastUpload_FallbackMessage')); ?>",
				dictFallbackText: "<?php echo addslashes($langs->transnoentities('FastUpload_FallbackText')); ?>",
				dictFileTooBig: "<?php echo addslashes($langs->transnoentities('FastUpload_FileTooBig')); ?>",
				dictInvalidFileType: "<?php echo addslashes($langs->transnoentities('FastUpload_InvalidFileType')); ?>",
				dictResponseError: "<?php echo addslashes($langs->transnoentities('FastUpload_ResponseError')); ?>",
				dictCancelUpload: "<?php echo addslashes($langs->transnoentities('FastUpload_CancelUpload')); ?>",
				dictCancelUploadConfirmation: "<?php echo addslashes($langs->transnoentities('FastUpload_CancelUploadConfirmation')); ?>",
				dictRemoveFile: "<?php echo addslashes($langs->transnoentities('FastUpload_RemoveFile')); ?>",
				dictRemoveFileConfirmation: "<?php echo addslashes($langs->transnoentities('FastUpload_RemoveFileConfirmation')); ?>",
				dictMaxFilesExceeded: "<?php echo addslashes($langs->transnoentities('FastUpload_MaxFilesExceeded')); ?>",

				init: function () {
					console.log('init');
					var dropzone = this;
					var form = $(this.options.clickable).closest("form");
					form.on("submit", function (e) {
						if (dropzone.getQueuedFiles().length) {
							e.preventDefault();
							e.stopPropagation();
							dropzone.processQueue();
						}
					});
				},
				fallback: function () {
					console.log('fallback');
					if ($("." + classPrefix).length) {
						$("." + classPrefix).hide();
					}
				},
				// Never call under 4.0 version
				successmultiple: function(files, response) {
					$("table.liste:first").replaceWith($(response).find("table.liste:first")); // DOL_VERSION < 6.0
					$("#tablelines").replaceWith($(response).find("#tablelines"));             // DOL_VERSION >= 6.0
					let $scriptEventMessages = $(response).find('#fastupload_htmloutput_events');
					if ($scriptEventMessages) {$(document.body).append($scriptEventMessages);}
					this.removeAllFiles();
				},
				success: function(file, response) {
					<?php if ((float) DOL_VERSION < 4.0) { ?>
						$("table.liste:first").replaceWith($(response).find("table.liste:first"));
						this.removeFile(file);
					<?php } ?>
				},
				error: function(file, response) {
					// Never called with fastupload because the backend is supposed to respond using HTTP response
					// codes, not with a Dolibarr page containing javascript aimed at displaying Event Messages
				}
			});

		} catch (e) {
			alert("<?php echo addslashes($langs->transnoentities('FastUpload_DropzoneNotSupported')); ?>");
		}
	};
	window.FastUpload = {};
	FastUpload.overrideForm = function(phpContext) {
		let $formuserfile = $('#formuserfile');
		var fu_action = $formuserfile.attr("action")
			,fu_method = $formuserfile.attr("method")
			,fu_paramName = $("#formuserfile input[type=file]").attr("name");
		var $inputSavingDocMask = $("#formuserfile input[name=savingdocmask]")

		var options = "";

		var dropzone_submit = $("#formuserfile input[type=submit]").parent().clone();
		$(dropzone_submit).find("input[type=file]").remove();
		dropzone_submit = $(dropzone_submit).html();

		var dropzone_savingdocmask = "";
		if ($inputSavingDocMask.length > 0)
		{
			dropzone_savingdocmask = $(
				'<div class="dropzone_savingdocmask">'
				// pourquoi le `clone()` ?
				+ $inputSavingDocMask.parent().clone().html()
				+ "</div>"
			);
		}

		var dropzone_div = $('<div class="dropzone center dz-clickable"></div>');
		dropzone_div.append($('<i class="upload-icon ace-icon fa fa-cloud-upload blue fa-3x"></i><br>'));
		console.log(phpContext.langs['FastUpload_DefaultMessage']);
		dropzone_div.append($('<span class="bigger-150 grey">' + phpContext.langs['FastUpload_DefaultMessage'] + '</span>'));
		dropzone_div.append($('<div id="dropzone-previews-box" class="dz dropzone-previews dz-max-files-reached"></div>'));

		if (phpContext.hookContexts.indexOf('adminconcatpdf') !== -1) {
			options += "<div>" + phpContext.options.replace(/\\n|\\r/, '') + "</div>";
		}

		var dropzone_form = $(`<form id="dropzone_form" action="${fu_action}" method="${fu_method}" enctype="multipart/form-data"></form>`);
		if(options !== "") dropzone_form.append(options);
		dropzone_form.append(dropzone_div);
		dropzone_form.append(
			'<br /><div ' + (phpContext.conf.FASTUPLOAD_ENABLE_AUTOUPLOAD ? 'style="display: none"' : '') + '>'
			+ dropzone_submit
			+ '</div>'
		);
		if (dropzone_savingdocmask) dropzone_form.append(dropzone_savingdocmask);
		dropzone_form.append($('<input type="hidden" name="fastupload_ajax" value="1" /><input type="hidden" name="token" value="<?php print $newToken; ?>" />'));


		$formuserfile.hide();
		$formuserfile.after(dropzone_form);

		fu_paramName = fu_paramName.replace("[", "");
		fu_paramName = fu_paramName.replace("]", "");

		enableDropzone($(dropzone_form), fu_paramName);

	};
});
