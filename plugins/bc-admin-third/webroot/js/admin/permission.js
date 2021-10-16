/**
 * baserCMS :  Based Website Development Project <https://basercms.net>
 * Copyright (c) baserCMS Users Community <https://basercms.net/community/>
 *
 * @copyright       Copyright (c) baserCMS Users Community
 * @link            https://basercms.net baserCMS Project
 * @since           baserCMS v 0.1.0
 * @license         https://basercms.net/license/index.html
 */

$(function () {

    $("#BtnMenuPermission").click(function () {
        $('#PermissionDialog').dialog('open');
        return false;
    });
    /**
     * バリデーション
     */
    $("#PermissionAjaxAddForm").validate();
    $("#PermissionAjaxAddForm").submit(function () {
        return false
    });
    
    

    /**
     * ダイアログを初期化
     */
    $("#PermissionDialog").dialog({
        bgiframe: true,
        autoOpen: false,
        width: 'auto',
        modal: true,
        open: function (event, ui) {
            $("#PermissionAjaxAddForm input").first().focus();
        },
        close: function () {
        },
        buttons: {
            cancel: {
                text: bcI18n.commonCancel,
                click: function () {
                    $(this).dialog('close');
                }
            },
            save: {
                text: bcI18n.commonSave,
                click: function () {
                    
                    $("#PermissionAjaxAddForm").submit();
                    if ($("#PermissionAjaxAddForm").valid()) {
                        $.bcToken.check(function () {
                            $('#PermissionAjaxAddForm input[name="_csrfToken"]').val($.bcToken.key);
                            $("#PermissionAjaxAddForm").ajaxSubmit({
                                beforeSend: function () {
                                    $("#Waiting").show();
                                },
                                success: function (response, status) {
                                    if (response) {
                                        $("#PermissionDialog").dialog('close');
                                    } else {
                                        alert(bcI18n.commonSaveFailedMessage);
                                    }
                                },
                                error: function (e) {
                                    alert(bcI18n.commonSaveFailedMessage);
                                },
                                complete: function () {
                                    $("#Waiting").hide();
                                }
                            });
                        });
                    }
                }
            }
        }
    });
});
