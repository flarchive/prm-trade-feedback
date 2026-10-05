import app from 'flarum/forum/app';
import FormModal from 'flarum/common/components/FormModal';
import Button from 'flarum/common/components/Button';
import Select from 'flarum/common/components/Select';
import Stream from 'flarum/common/utils/Stream';

function ratingKey(rating) {
  if (rating === 1 || rating === '1' || rating === 'positive') {
    return 'positive';
  }
  if (rating === -1 || rating === '-1' || rating === 'negative') {
    return 'negative';
  }
  if (rating === 0 || rating === '0' || rating === 'neutral') {
    return 'neutral';
  }
  return '';
}

function ratingNumber(value) {
  if (value === 'positive') {
    return 1;
  }
  if (value === 'negative') {
    return -1;
  }
  if (value === 'neutral') {
    return 0;
  }
  return parseInt(value, 10);
}

export default class LeaveFeedbackModal extends FormModal {
  oninit(vnode) {
    super.oninit(vnode);

    const existing = this.attrs.feedback;

    this.role = Stream(existing ? existing.role() : '');
    this.rating = Stream(existing ? ratingKey(existing.rating()) : '');
    this.shortComment = Stream(existing ? existing.shortComment() : '');
    this.threadUrl = Stream(existing && existing.threadUrl() ? existing.threadUrl() : '');
    this.comment = Stream(existing && existing.comment() ? existing.comment() : '');
  }

  className() {
    return 'LeaveFeedbackModal Modal--large';
  }

  title() {
    return app.translator.trans('prm-trade-feedback.forum.leave_title', {
      username: this.attrs.user.displayName(),
    });
  }

  content() {
    const notice = app.forum.attribute('tradeFeedbackNotice');

    return (
      <div className="Modal-body">
        <div className="Form">
          <div className="Form-group">
            <label>{app.translator.trans('prm-trade-feedback.forum.form.role')}</label>
            <Select
              value={this.role()}
              onchange={this.role}
              options={{
                '': app.translator.trans('prm-trade-feedback.forum.form.role_placeholder'),
                buyer: app.translator.trans('prm-trade-feedback.forum.form.role_buyer'),
                seller: app.translator.trans('prm-trade-feedback.forum.form.role_seller'),
                trade: app.translator.trans('prm-trade-feedback.forum.form.role_trade'),
              }}
            />
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('prm-trade-feedback.forum.form.rating')}</label>
            <Select
              value={this.rating()}
              onchange={this.rating}
              options={{
                '': app.translator.trans('prm-trade-feedback.forum.form.role_placeholder'),
                positive: app.translator.trans('prm-trade-feedback.forum.form.rating_positive'),
                neutral: app.translator.trans('prm-trade-feedback.forum.form.rating_neutral'),
                negative: app.translator.trans('prm-trade-feedback.forum.form.rating_negative'),
              }}
            />
            {notice ? <p className="helpText">{notice}</p> : null}
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('prm-trade-feedback.forum.form.short_comment')}</label>
            <input className="FormControl" bidi={this.shortComment} maxlength="80" />
            <p className="helpText">{app.translator.trans('prm-trade-feedback.forum.form.short_help')}</p>
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('prm-trade-feedback.forum.form.thread_url')}</label>
            <input className="FormControl" bidi={this.threadUrl} />
            <p className="helpText">{app.translator.trans('prm-trade-feedback.forum.form.thread_help')}</p>
          </div>

          <div className="Form-group">
            <label>{app.translator.trans('prm-trade-feedback.forum.form.comment')}</label>
            <textarea className="FormControl" bidi={this.comment} />
          </div>

          <div className="Form-group">
            <Button className="Button Button--primary" type="submit" loading={this.loading}>
              {app.translator.trans('prm-trade-feedback.forum.form.submit')}
            </Button>
          </div>
        </div>
      </div>
    );
  }

  onsubmit(e) {
    e.preventDefault();

    this.loading = true;

    const attrs = {
      role: this.role(),
      rating: ratingNumber(this.rating()),
      shortComment: this.shortComment(),
      comment: this.comment(),
      threadUrl: this.threadUrl(),
    };

    const existing = this.attrs.feedback;
    const request = existing
      ? existing.save(attrs)
      : app.store.createRecord('trade-feedbacks').save({
          ...attrs,
          relationships: { toUser: this.attrs.user },
        });

    request
      .then(() => {
        if (this.attrs.onsaved) {
          this.attrs.onsaved();
        }
        this.hide();
      })
      .catch(() => {
        this.loading = false;
        m.redraw();
      });
  }
}
