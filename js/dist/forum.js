(function () {
var app = flarum.core.compat['forum/app'] || flarum.core.compat.app;
var extendMod = flarum.core.compat['common/extend'] || {};
var extend = extendMod.extend;
var Model = flarum.core.compat['common/Model'];
var User = flarum.core.compat['common/models/User'];
var UserPage = flarum.core.compat['forum/components/UserPage'];
var LinkButton = flarum.core.compat['common/components/LinkButton'];
var Button = flarum.core.compat['common/components/Button'];
var Select = flarum.core.compat['common/components/Select'];
var Link = flarum.core.compat['common/components/Link'];
var Modal = flarum.core.compat['common/components/Modal'];
var LoadingIndicator = flarum.core.compat['common/components/LoadingIndicator'];
var UserControls = flarum.core.compat['forum/utils/UserControls'];
var NotificationGrid = flarum.core.compat['forum/components/NotificationGrid'];
var Notification = flarum.core.compat['forum/components/Notification'];
var Stream = flarum.core.compat['common/utils/Stream'];
var icon = flarum.core.compat['common/helpers/icon'];
var username = flarum.core.compat['common/helpers/username'];
var extractText = flarum.core.compat['common/utils/extractText'];
var m = window.m;

function t(key, params) {
  return app.translator.trans('prm-trade-feedback.forum.' + key, params || {});
}

function formatDate(value) {
  if (!value) {
    return '';
  }
  var date = value instanceof Date ? value : new Date(value);
  if (isNaN(date.getTime())) {
    return '';
  }
  var dd = String(date.getDate()).padStart(2, '0');
  var mm = String(date.getMonth() + 1).padStart(2, '0');
  return dd + '-' + mm + '-' + date.getFullYear();
}

function ratingClass(rating) {
  if (rating > 0) {
    return 'positive';
  }
  if (rating < 0) {
    return 'negative';
  }
  return 'neutral';
}

function ratingIconName(rating) {
  if (rating > 0) {
    return 'fas fa-check-circle';
  }
  if (rating < 0) {
    return 'fas fa-minus-circle';
  }
  return 'fas fa-info-circle';
}

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

class TradeFeedback extends Model {}
TradeFeedback.prototype.role = Model.attribute('role');
TradeFeedback.prototype.rating = Model.attribute('rating');
TradeFeedback.prototype.shortComment = Model.attribute('shortComment');
TradeFeedback.prototype.comment = Model.attribute('comment');
TradeFeedback.prototype.threadUrl = Model.attribute('threadUrl');
TradeFeedback.prototype.createdAt = Model.attribute('createdAt', Model.transformDate);
TradeFeedback.prototype.canEdit = Model.attribute('canEdit');
TradeFeedback.prototype.canDelete = Model.attribute('canDelete');
TradeFeedback.prototype.fromUser = Model.hasOne('fromUser');
TradeFeedback.prototype.toUser = Model.hasOne('toUser');

class LeaveFeedbackModal extends Modal {
  oninit(vnode) {
    super.oninit(vnode);
    var existing = this.attrs.feedback;
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
    return t('leave_title', { username: this.attrs.user.displayName() });
  }

  content() {
    var self = this;
    var notice = app.forum.attribute('tradeFeedbackNotice');

    return m('div.Modal-body', m('div.Form', [
      m('div.Form-group', [
        m('label', t('form.role')),
        m(Select, {
          value: this.role(),
          onchange: this.role,
          options: {
            '': t('form.role_placeholder'),
            buyer: t('form.role_buyer'),
            seller: t('form.role_seller'),
            trade: t('form.role_trade'),
          },
        }),
      ]),
      m('div.Form-group', [
        m('label', t('form.rating')),
        m(Select, {
          value: this.rating(),
          onchange: this.rating,
          options: {
            '': t('form.role_placeholder'),
            positive: t('form.rating_positive'),
            neutral: t('form.rating_neutral'),
            negative: t('form.rating_negative'),
          },
        }),
        notice ? m('p.helpText', notice) : null,
      ]),
      m('div.Form-group', [
        m('label', t('form.short_comment')),
        m('input.FormControl', {
          value: this.shortComment(),
          maxlength: 80,
          oninput: function (e) {
            self.shortComment(e.target.value);
          },
        }),
        m('p.helpText', t('form.short_help')),
      ]),
      m('div.Form-group', [
        m('label', t('form.thread_url')),
        m('input.FormControl', {
          value: this.threadUrl(),
          oninput: function (e) {
            self.threadUrl(e.target.value);
          },
        }),
        m('p.helpText', t('form.thread_help')),
      ]),
      m('div.Form-group', [
        m('label', t('form.comment')),
        m('textarea.FormControl', {
          value: this.comment(),
          oninput: function (e) {
            self.comment(e.target.value);
          },
        }),
      ]),
      m(
        'div.Form-group',
        m(
          Button,
          { className: 'Button Button--primary', type: 'submit', loading: this.loading },
          t('form.submit')
        )
      ),
    ]));
  }

  onsubmit(e) {
    e.preventDefault();
    this.loading = true;

    var attrs = {
      role: this.role(),
      rating: ratingNumber(this.rating()),
      shortComment: this.shortComment(),
      comment: this.comment(),
      threadUrl: this.threadUrl(),
    };

    var existing = this.attrs.feedback;
    var request = existing
      ? existing.save(attrs)
      : app.store.createRecord('trade-feedbacks').save(
          Object.assign({}, attrs, {
            relationships: { toUser: this.attrs.user },
          })
        );

    var self = this;
    request
      .then(function () {
        if (self.attrs.onsaved) {
          self.attrs.onsaved();
        }
        self.hide();
      })
      .catch(function () {
        self.loading = false;
        m.redraw();
      });
  }
}

class TradeFeedbackNotification extends Notification {
  icon() {
    return 'fas fa-handshake';
  }

  href() {
    var user = app.session.user;
    return user ? app.route('user.trade', { username: user.slug() }) : app.forum.attribute('baseUrl');
  }

  content() {
    var fromUser = this.attrs.notification.fromUser();
    return t('notifications.received', { username: fromUser ? fromUser.displayName() : '' });
  }
}

class TradeUserPage extends UserPage {
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
      return m(LoadingIndicator);
    }

    var self = this;
    var stats = this.user.tradeFeedbackStats() || {};
    var periods = stats.periods || {};
    var current = periods[this.period] || { positive: 0, neutral: 0, negative: 0, percent: 0 };
    var max = Math.max(current.positive, current.neutral, current.negative, 1);
    var pages = Math.max(1, Math.ceil(this.total / this.perPage));
    var pageButtons = [];
    var i;

    for (i = 1; i <= pages; i++) {
      pageButtons.push(this.pageButton(i));
    }

    return m('div.TradePage', [
      this.user.canGiveTradeFeedback()
        ? m(
            'div.TradePage-head',
            m(
              Button,
              {
                className: 'Button Button--primary',
                icon: 'fas fa-plus',
                onclick: function () {
                  self.openModal();
                },
              },
              t('leave_button')
            )
          )
        : null,
      m('div.TradePage-panel', m('div.TradePage-stats', [
        m('dl', [
          this.statRow('trade', stats.received || 0),
          this.statRow('percent', (stats.percent || 0) + '%'),
          this.statRow('given_positive', stats.givenPositive || 0),
          this.statRow('given_neutral', stats.givenNeutral || 0),
          this.statRow('given_negative', stats.givenNegative || 0),
          this.statRow('total_positive', stats.receivedPositive || 0),
        ]),
        m('div', [
          m('div.TradePage-periods', ['30d', '6m', '1y'].map(function (key) {
            return m(
              'button.TradePage-period' + (self.period === key ? '.active' : ''),
              {
                type: 'button',
                onclick: function () {
                  self.period = key;
                },
              },
              t('stats.last_' + key)
            );
          })),
          this.meter('positive', current.positive, current.percent, max),
          this.meter('neutral', current.neutral, 0, max),
          this.meter('negative', current.negative, 0, max),
        ]),
      ])),
      m('div.TradePage-panel', [
        m('div.TradePage-toolbar', [
          m(Select, {
            value: this.direction,
            onchange: function (value) {
              self.direction = value;
              self.pageNumber = 1;
              self.refresh();
            },
            options: { received: t('filters.received'), given: t('filters.given') },
          }),
          m(Select, {
            value: this.rating,
            onchange: function (value) {
              self.rating = value;
              self.pageNumber = 1;
              self.refresh();
            },
            options: {
              all: t('filters.all'),
              positive: t('filters.positive'),
              neutral: t('filters.neutral'),
              negative: t('filters.negative'),
            },
          }),
        ]),
        this.loadingList
          ? m(LoadingIndicator)
          : this.feedbacks.length
            ? m(
                'div.TradePage-tableWrap',
                m('table.TradePage-table', [
                  m('thead', m('tr', [
                    m('th'),
                    m('th', t('table.options')),
                    m('th', this.direction === 'given' ? t('table.receiver') : t('table.sender')),
                    m('th', t('table.date')),
                  ])),
                  m(
                    'tbody',
                    this.feedbacks.map(function (item) {
                      return self.row(item);
                    })
                  ),
                ])
              )
            : m('p', t('table.empty')),
        pages > 1 ? m('div.TradePage-pager', pageButtons) : null,
      ]),
    ]);
  }

  pageButton(page) {
    var self = this;
    return m(
      Button,
      {
        className: 'Button' + (page === this.pageNumber ? ' Button--primary' : ''),
        onclick: function () {
          self.pageNumber = page;
          self.refresh();
        },
      },
      String(page)
    );
  }

  statRow(key, value) {
    return m('div.TradePage-statRow', [m('dt', t('stats.' + key)), m('dd', value)]);
  }

  meter(key, count, percent, max) {
    var width = Math.round((count / max) * 100);
    var rating = key === 'positive' ? 1 : key === 'negative' ? -1 : 0;

    return m('div.TradePage-meter.TradePage-meter--' + key, [
      icon(ratingIconName(rating), { className: 'TradePage-icon--' + key }),
      m('span', t('stats.' + key)),
      m('div.TradePage-meterBar', count ? m('span', { style: { width: width + '%' } }) : null),
      m('strong', count + (key === 'positive' && percent ? ' (%' + percent + ')' : '')),
    ]);
  }

  row(item) {
    var self = this;
    var other = this.direction === 'given' ? item.toUser() : item.fromUser();
    var rating = item.rating();

    return m('tr', [
      m(
        'td',
        m('div.TradePage-comment', [
          icon(ratingIconName(rating), { className: 'TradePage-icon--' + ratingClass(rating) }),
          m('span', item.shortComment()),
        ])
      ),
      m('td', [
        item.canEdit()
          ? m(Button, {
              className: 'Button Button--icon',
              icon: 'fas fa-pencil-alt',
              onclick: function () {
                self.openModal(item);
              },
            })
          : null,
        item.canDelete()
          ? m(Button, {
              className: 'Button Button--icon',
              icon: 'fas fa-trash',
              onclick: function () {
                self.deleteItem(item);
              },
            })
          : null,
      ]),
      m(
        'td',
        other
          ? m(Link, { href: app.route.user(other) }, [username(other), ' (', other.tradeReceivedCount() || 0, ')'])
          : null
      ),
      m('td', formatDate(item.createdAt())),
    ]);
  }

  openModal(feedback) {
    var self = this;
    app.modal.show(LeaveFeedbackModal, {
      user: this.user,
      feedback: feedback,
      onsaved: function () {
        self.reloadUserAndList();
      },
    });
  }

  deleteItem(item) {
    var self = this;
    if (!window.confirm(extractText(t('actions.confirm_delete')))) {
      return;
    }
    item.delete().then(function () {
      self.reloadUserAndList();
    });
  }

  reloadUserAndList() {
    var self = this;
    app.store.find('users', this.user.id()).then(function (user) {
      self.user = user;
      self.refresh();
    });
  }

  refresh() {
    if (!this.user) {
      return;
    }

    var self = this;
    this.loadingList = true;

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
          page: { offset: (this.pageNumber - 1) * this.perPage, limit: this.perPage },
          include: 'fromUser,toUser',
        },
      })
      .then(function (payload) {
        self.total = (payload.meta && payload.meta.total) || 0;
        self.feedbacks = app.store.pushPayload(payload);
        self.loadingList = false;
        m.redraw();
      })
      .catch(function () {
        self.loadingList = false;
        m.redraw();
      });
  }
}

app.initializers.add('prm-trade-feedback', function () {
  app.store.models['trade-feedbacks'] = TradeFeedback;
  app.routes['user.trade'] = { path: '/u/:username/trade', component: TradeUserPage };
  app.notificationComponents.tradeFeedbackReceived = TradeFeedbackNotification;

  User.prototype.tradeFeedbackStats = Model.attribute('tradeFeedbackStats');
  User.prototype.canGiveTradeFeedback = Model.attribute('canGiveTradeFeedback');
  User.prototype.tradeReceivedCount = Model.attribute('tradeReceivedCount');

  extend(UserPage.prototype, 'navItems', function (items) {
    var user = this.user;
    if (!user) {
      return;
    }
    items.add(
      'trade',
      m(
        LinkButton,
        { href: app.route('user.trade', { username: user.slug() }), icon: 'fas fa-handshake' },
        t('nav')
      ),
      85
    );
  });

  extend(UserControls, 'userControls', function (items, user) {
    if (!user || !user.canGiveTradeFeedback || !user.canGiveTradeFeedback()) {
      return;
    }
    items.add(
      'trade-feedback',
      m(
        Button,
        {
          icon: 'fas fa-handshake',
          onclick: function () {
            app.modal.show(LeaveFeedbackModal, { user: user });
          },
        },
        t('user_controls')
      ),
      80
    );
  });

  extend(NotificationGrid.prototype, 'notificationTypes', function (items) {
    items.add('tradeFeedbackReceived', {
      name: 'tradeFeedbackReceived',
      icon: 'fas fa-handshake',
      label: t('notifications.notify_received_label'),
    });
  });
});

module.exports = {};
})();
