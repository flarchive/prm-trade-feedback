import app from 'flarum/admin/app';
import Extend from 'flarum/common/extenders';

export default [
  new Extend.Admin()
    .permission(
      () => ({
        icon: 'fas fa-handshake',
        label: app.translator.trans('prm-trade-feedback.admin.permissions.give_label'),
        permission: 'user.giveTradeFeedback',
      }),
      'start'
    )
    .permission(
      () => ({
        icon: 'fas fa-gavel',
        label: app.translator.trans('prm-trade-feedback.admin.permissions.moderate_label'),
        permission: 'user.moderateTradeFeedback',
      }),
      'moderate'
    )
    .setting(() => ({
      setting: 'prm-trade-feedback.notice',
      type: 'text',
      label: app.translator.trans('prm-trade-feedback.admin.settings.notice_label'),
      help: app.translator.trans('prm-trade-feedback.admin.settings.notice_help'),
    })),
];
