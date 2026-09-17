(function () {
var app = flarum.core.compat['admin/app'] || flarum.core.compat.app;

app.initializers.add('prm-trade-feedback', function () {
  app.extensionData
    .for('prm-trade-feedback')
    .registerPermission(
      {
        icon: 'fas fa-handshake',
        label: app.translator.trans('prm-trade-feedback.admin.permissions.give_label'),
        permission: 'user.giveTradeFeedback',
      },
      'start'
    )
    .registerPermission(
      {
        icon: 'fas fa-gavel',
        label: app.translator.trans('prm-trade-feedback.admin.permissions.moderate_label'),
        permission: 'user.moderateTradeFeedback',
      },
      'moderate'
    )
    .registerSetting({
      setting: 'prm-trade-feedback.notice',
      type: 'text',
      label: app.translator.trans('prm-trade-feedback.admin.settings.notice_label'),
      help: app.translator.trans('prm-trade-feedback.admin.settings.notice_help'),
    });
});

module.exports = {};
})();
