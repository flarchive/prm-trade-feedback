import Extend from 'flarum/common/extenders';
import User from 'flarum/common/models/User';
import UserPageResolver from 'flarum/forum/resolvers/UserPageResolver';
import TradeFeedback from './models/TradeFeedback';
import TradeUserPage from './pages/TradeUserPage';
import TradeFeedbackNotification from './components/TradeFeedbackNotification';

export default [
  new Extend.Store().add('trade-feedbacks', TradeFeedback),

  new Extend.Model(User)
    .attribute('tradeFeedbackStats')
    .attribute('canGiveTradeFeedback')
    .attribute('tradeReceivedCount'),

  new Extend.Routes().add('user.trade', '/u/:username/trade', TradeUserPage, UserPageResolver),

  new Extend.Notification().add('tradeFeedbackReceived', TradeFeedbackNotification),
];
