import app from 'flarum/forum/app';
import Model from 'flarum/common/Model';

export default class TradeFeedback extends Model {}

Object.assign(TradeFeedback.prototype, {
  role: Model.attribute('role'),
  rating: Model.attribute('rating'),
  shortComment: Model.attribute('shortComment'),
  comment: Model.attribute('comment'),
  threadUrl: Model.attribute('threadUrl'),
  createdAt: Model.attribute('createdAt', Model.transformDate),
  canEdit: Model.attribute('canEdit'),
  canDelete: Model.attribute('canDelete'),
  fromUser: Model.hasOne('fromUser'),
  toUser: Model.hasOne('toUser'),
});
