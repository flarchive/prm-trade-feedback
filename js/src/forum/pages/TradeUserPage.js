import app from 'flarum/forum/app';
import UserPage from 'flarum/forum/components/UserPage';
import Button from 'flarum/common/components/Button';
import Link from 'flarum/common/components/Link';
import Select from 'flarum/common/components/Select';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import icon from 'flarum/common/helpers/icon';
import username from 'flarum/common/helpers/username';
import LeaveFeedbackModal from '../components/LeaveFeedbackModal';

function t(key, params) {
  return app.translator.trans('prm-trade-feedback.forum.' + key, params || {});
}

function formatDate(value) {
  if (!value) {
    return '';
  }
  const date = value instanceof Date ? value : new Date(value);
  const dd = String(date.getDate()).padStart(2, '0');
  const mm = String(date.getMonth() + 1).padStart(2, '0');
  return dd + '-' + mm + '-' + date.getFullYear();
}

function ratingClass(rating) {
  if (rating > 0) return 'positive';
  if (rating < 0) return 'negative';
  return 'neutral';
}

function ratingIcon(rating) {
  if (rating > 0) return 'fas fa-check-circle';
  if (rating < 0) return 'fas fa-minus-circle';
  return 'fas fa-info-circle';
}

export default class TradeUserPage extends UserPage {
  oninit(vnode) {
    super.oninit(vnode);

    this.direction = 'received';
    this.rating = 'all';
    this.period = '30d';
    this.pageNumber = 1;
    this.perPage = 10;
    this.total = 0;
    this.loadingList = true;
    this.feedbacks = [];

    this.loadUser(m.route.param('username'));
  }

  show(user) {
    super.show(user);
    this.refresh();
  }

  content() {
    if (!this.user) {
      return <LoadingIndicator />;
    }

    const stats = this.user.tradeFeedbackStats() || {};
    const periods = stats.periods || {};
    const current = periods[this.period] || { positive: 0, neutral: 0, negative: 0, percent: 0 };
    const max = Math.max(current.positive, current.neutral, current.negative, 1);
    const pages = Math.max(1, Math.ceil(this.total / this.perPage));

    return (
      <div className="TradePage">
        {this.user.canGiveTradeFeedback() ? (
          <div className="TradePage-head">
            <Button className="Button Button--primary" icon="fas fa-plus" onclick={() => this.openModal()}>
              {t('leave_button')}
            </Button>
          </div>
        ) : null}

        <div className="TradePage-panel">
          <div className="TradePage-stats">
            <dl>
              {this.statRow('trade', stats.received || 0)}
              {this.statRow('percent', (stats.percent || 0) + '%')}
              {this.statRow('given_positive', stats.givenPositive || 0)}
              {this.statRow('given_neutral', stats.givenNeutral || 0)}
              {this.statRow('given_negative', stats.givenNegative || 0)}
              {this.statRow('total_positive', stats.receivedPositive || 0)}
            </dl>
            <div>
              <div className="TradePage-periods">
                {['30d', '6m', '1y'].map((key) => (
                  <button
                    className={'TradePage-period' + (this.period === key ? ' active' : '')}
                    type="button"
                    onclick={() => {
                      this.period = key;
                    }}
                  >
                    {t('stats.last_' + key)}
                  </button>
                ))}
              </div>
              {this.meter('positive', current.positive, current.percent, max)}
              {this.meter('neutral', current.neutral, 0, max)}
              {this.meter('negative', current.negative, 0, max)}
            </div>
          </div>
        </div>

        <div className="TradePage-panel">
          <div className="TradePage-toolbar">
            <Select
              value={this.direction}
              onchange={(value) => {
                this.direction = value;
                this.pageNumber = 1;
                this.refresh();
              }}
              options={{ received: t('filters.received'), given: t('filters.given') }}
            />
            <Select
              value={this.rating}
              onchange={(value) => {
                this.rating = value;
                this.pageNumber = 1;
                this.refresh();
              }}
              options={{
                all: t('filters.all'),
                positive: t('filters.positive'),
                neutral: t('filters.neutral'),
                negative: t('filters.negative'),
              }}
            />
          </div>

          {this.loadingList ? (
            <LoadingIndicator />
          ) : this.feedbacks.length ? (
            <div className="TradePage-tableWrap">
              <table className="TradePage-table">
                <thead>
                  <tr>
                    <th />
                    <th>{t('table.options')}</th>
                    <th>{this.direction === 'given' ? t('table.receiver') : t('table.sender')}</th>
                    <th>{t('table.date')}</th>
                  </tr>
                </thead>
                <tbody>{this.feedbacks.map((item) => this.row(item))}</tbody>
              </table>
            </div>
          ) : (
            <p>{t('table.empty')}</p>
          )}

          {pages > 1 ? (
            <div className="TradePage-pager">
              {Array.from({ length: pages }, (_, i) => i + 1).map((page) => (
                <Button
                  className={'Button' + (page === this.pageNumber ? ' Button--primary' : '')}
                  onclick={() => {
                    this.pageNumber = page;
                    this.refresh();
                  }}
                >
                  {page}
                </Button>
              ))}
            </div>
          ) : null}
        </div>
      </div>
    );
  }

  statRow(key, value) {
    return (
      <div className="TradePage-statRow">
        <dt>{t('stats.' + key)}</dt>
        <dd>{value}</dd>
      </div>
    );
  }

  meter(key, count, percent, max) {
    const width = Math.round((count / max) * 100);

    return (
      <div className={'TradePage-meter TradePage-meter--' + key}>
        {icon(ratingIcon(key === 'positive' ? 1 : key === 'negative' ? -1 : 0), { className: 'TradePage-icon--' + key })}
        <span>{t('stats.' + key)}</span>
        <div className="TradePage-meterBar">{count ? <span style={'width:' + width + '%'} /> : null}</div>
        <strong>
          {count}
          {key === 'positive' && percent ? ' (%' + percent + ')' : ''}
        </strong>
      </div>
    );
  }

  row(item) {
    const other = this.direction === 'given' ? item.toUser() : item.fromUser();
    const rating = item.rating();

    return (
      <tr>
        <td>
          <div className="TradePage-comment">
            {icon(ratingIcon(rating), { className: 'TradePage-icon--' + ratingClass(rating) })}
            <span>{item.shortComment()}</span>
          </div>
        </td>
        <td>
          {item.canEdit() ? (
            <Button className="Button Button--icon" icon="fas fa-pencil-alt" onclick={() => this.openModal(item)} />
          ) : null}
          {item.canDelete() ? (
            <Button className="Button Button--icon" icon="fas fa-trash" onclick={() => this.deleteItem(item)} />
          ) : null}
        </td>
        <td>
          {other ? (
            <Link href={app.route.user(other)}>
              {username(other)} ({other.tradeReceivedCount() || 0})
            </Link>
          ) : null}
        </td>
        <td>{formatDate(item.createdAt())}</td>
      </tr>
    );
  }

  openModal(feedback) {
    app.modal.show(LeaveFeedbackModal, {
      user: this.user,
      feedback,
      onsaved: () => this.reloadUserAndList(),
    });
  }

  deleteItem(item) {
    if (!confirm(app.translator.trans('prm-trade-feedback.forum.actions.confirm_delete'))) {
      return;
    }

    item.delete().then(() => this.reloadUserAndList());
  }

  reloadUserAndList() {
    app.store.find('users', this.user.id()).then((user) => {
      this.user = user;
      this.refresh();
    });
  }

  refresh() {
    if (!this.user) {
      return;
    }

    this.loadingList = true;

    const offset = (this.pageNumber - 1) * this.perPage;

    app
      .request({
        method: 'GET',
        url: app.forum.attribute('apiUrl') + '/trade-feedbacks',
        params: {
          filter: {
            user: this.user.id(),
            direction: this.direction,
            rating: this.rating,
          },
          page: { offset, limit: this.perPage },
          include: 'fromUser,toUser',
        },
      })
      .then((payload) => {
        this.total = (payload.meta && payload.meta.total) || 0;
        this.feedbacks = app.store.pushPayload(payload);
        this.loadingList = false;
        m.redraw();
      })
      .catch(() => {
        this.loadingList = false;
        m.redraw();
      });
  }
}
