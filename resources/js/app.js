import './bootstrap';
import { createApp } from 'vue';
import RentalsDashboard from './components/rentals/Dashboard.vue';
import RentalAgreementList from './components/rentals/AgreementList.vue';
import RentalItemList from './components/rentals/ItemList.vue';

// Create Vue application
const app = createApp({});

// Register components
app.component('rentals-dashboard', RentalsDashboard);
app.component('rental-agreement-list', RentalAgreementList);
app.component('rental-item-list', RentalItemList);

// Mount the app
app.mount('#app');