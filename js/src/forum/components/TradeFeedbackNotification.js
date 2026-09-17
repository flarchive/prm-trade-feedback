import app from 'flarum/forum/app';
import Notification from 'flarum/forum/components/Notification';

export default class TradeFeedbackNotification extends Notification {
  icon() {
    return 'fas fa-handshake';
  }

  href() {
    const user = app.session.user;
    return user ? app.route('user.trade', { username: user.slug() }) : app.forum.attribute('baseUrl');
  }

  content() {
    const fromUser = this.attrs.notification.fromUser();

    return app.translator.trans('prm-trade-feedback.forum.notifications.received', {
      username: fromUser ? fromUser.displayName() : '',
    });
  }
}
