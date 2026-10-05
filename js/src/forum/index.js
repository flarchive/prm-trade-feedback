import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import LinkButton from 'flarum/common/components/LinkButton';
import Button from 'flarum/common/components/Button';
import UserControls from 'flarum/forum/utils/UserControls';
import LeaveFeedbackModal from './components/LeaveFeedbackModal';

export { default as extend } from './extend';

app.initializers.add('prm-trade-feedback', () => {
  extend('flarum/forum/components/UserPage', 'navItems', function (items) {
    const user = this.user;
    if (!user) {
      return;
    }

    items.add(
      'trade',
      <LinkButton href={app.route('user.trade', { username: user.slug() })} icon="fas fa-handshake">
        {app.translator.trans('prm-trade-feedback.forum.nav')}
      </LinkButton>,
      85
    );
  });

  extend(UserControls, 'userControls', function (items, user) {
    if (!user || !user.canGiveTradeFeedback || !user.canGiveTradeFeedback()) {
      return;
    }

    items.add(
      'trade-feedback',
      <Button icon="fas fa-handshake" onclick={() => app.modal.show(LeaveFeedbackModal, { user })}>
        {app.translator.trans('prm-trade-feedback.forum.user_controls')}
      </Button>,
      80
    );
  });

  extend('flarum/forum/components/NotificationGrid', 'notificationTypes', function (items) {
    items.add('tradeFeedbackReceived', {
      name: 'tradeFeedbackReceived',
      icon: 'fas fa-handshake',
      label: app.translator.trans('prm-trade-feedback.forum.notifications.notify_received_label'),
    });
  });
});
