import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import UserPage from 'flarum/forum/components/UserPage';
import LinkButton from 'flarum/common/components/LinkButton';
import Button from 'flarum/common/components/Button';
import UserControls from 'flarum/forum/utils/UserControls';
import NotificationGrid from 'flarum/forum/components/NotificationGrid';
import Model from 'flarum/common/Model';
import User from 'flarum/common/models/User';
import TradeFeedback from './models/TradeFeedback';
import TradeUserPage from './pages/TradeUserPage';
import LeaveFeedbackModal from './components/LeaveFeedbackModal';
import TradeFeedbackNotification from './components/TradeFeedbackNotification';

export default function addTradePage() {
  app.store.models['trade-feedbacks'] = TradeFeedback;
  app.routes['user.trade'] = { path: '/u/:username/trade', component: TradeUserPage };
  app.notificationComponents.tradeFeedbackReceived = TradeFeedbackNotification;

  User.prototype.tradeFeedbackStats = Model.attribute('tradeFeedbackStats');
  User.prototype.canGiveTradeFeedback = Model.attribute('canGiveTradeFeedback');
  User.prototype.tradeReceivedCount = Model.attribute('tradeReceivedCount');

  extend(UserPage.prototype, 'navItems', function (items) {
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

  extend(NotificationGrid.prototype, 'notificationTypes', function (items) {
    items.add('tradeFeedbackReceived', {
      name: 'tradeFeedbackReceived',
      icon: 'fas fa-handshake',
      label: app.translator.trans('prm-trade-feedback.forum.notifications.notify_received_label'),
    });
  });
}
