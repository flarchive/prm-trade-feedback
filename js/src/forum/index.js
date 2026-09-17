import app from 'flarum/forum/app';
import addTradePage from './addTradePage';

app.initializers.add('prm-trade-feedback', () => {
  addTradePage();
});
