<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 mb-0">Rentals Dashboard</h1>
        <p class="text-muted">Overview of rental operations and performance</p>
      </div>
      <div>
        <a :href="createAgreementUrl" class="btn btn-primary">
          <i class="fas fa-plus me-1"></i>New Rental Agreement
        </a>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
      <div class="col-xl-3 col-md-6" v-for="(stat, index) in stats" :key="index">
        <div class="card h-100 py-2" :class="stat.borderClass">
          <div class="card-body">
            <div class="row no-gutters align-items-center">
              <div class="col mr-2">
                <div class="text-xs font-weight-bold text-uppercase mb-1" :class="stat.textClass">
                  {{ stat.title }}
                </div>
                <div class="h5 mb-0 font-weight-bold text-gray-800">
                  {{ stat.value }}
                </div>
                <div v-if="stat.progress" class="progress progress-sm mt-2">
                  <div class="progress-bar" :class="stat.progressClass" role="progressbar" 
                       :style="{ width: stat.progressValue + '%' }"></div>
                </div>
              </div>
              <div class="col-auto">
                <i class="fas fa-2x text-gray-300" :class="stat.icon"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <!-- Recent Agreements -->
      <div class="col-lg-8">
        <div class="card mb-4">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Recent Rental Agreements</h6>
            <a :href="agreementsIndexUrl" class="btn btn-sm btn-primary">View All</a>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead class="table-light">
                  <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                    <th>Amount</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="agreement in recentAgreements" :key="agreement.id">
                    <td>
                      <a :href="getAgreementUrl(agreement.id)" class="fw-bold text-decoration-none">
                        #{{ agreement.id }}
                      </a>
                    </td>
                    <td>{{ agreement.customer_name }}</td>
                    <td>{{ formatDate(agreement.start_date) }}</td>
                    <td>{{ formatDate(agreement.end_date) }}</td>
                    <td>
                      <span class="badge" :class="getStatusClass(agreement.status)">
                        {{ capitalize(agreement.status) }}
                      </span>
                    </td>
                    <td>${{ formatNumber(agreement.total_amount) }}</td>
                  </tr>
                  <tr v-if="recentAgreements.length === 0">
                    <td colspan="6" class="text-center py-3">No rental agreements found</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Upcoming Returns -->
      <div class="col-lg-4">
        <div class="card mb-4">
          <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary">Upcoming Returns</h6>
          </div>
          <div class="card-body p-0">
            <div class="list-group list-group-flush">
              <a v-for="return_item in upcomingReturns" 
                 :key="return_item.id" 
                 :href="getAgreementUrl(return_item.id)" 
                 class="list-group-item list-group-item-action">
                <div class="d-flex w-100 justify-content-between">
                  <h6 class="mb-1">{{ return_item.customer_name }}</h6>
                  <small :class="getReturnDateClass(return_item.end_date)">
                    {{ formatShortDate(return_item.end_date) }}
                  </small>
                </div>
                <p class="mb-1">{{ return_item.items_count }} item(s)</p>
                <small>${{ formatNumber(return_item.total_amount) }}</small>
              </a>
              <div v-if="upcomingReturns.length === 0" class="list-group-item text-center py-3">
                No upcoming returns
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Monthly Revenue Chart -->
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary">Monthly Revenue ({{ currentYear }})</h6>
          </div>
          <div class="card-body">
            <canvas ref="revenueChart" height="100"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Chart from 'chart.js/auto';

export default {
  props: {
    dashboardData: {
      type: Object,
      required: true
    },
    routes: {
      type: Object,
      required: true
    }
  },
  data() {
    return {
      chart: null,
      currentYear: new Date().getFullYear()
    };
  },
  computed: {
    stats() {
      return [
        {
          title: 'Active Rentals',
          value: this.dashboardData.activeAgreements,
          icon: 'fa-clipboard-list',
          borderClass: 'border-left-primary',
          textClass: 'text-primary'
        },
        {
          title: 'Overdue Returns',
          value: this.dashboardData.overdueAgreements,
          icon: 'fa-calendar-times',
          borderClass: 'border-left-danger',
          textClass: 'text-danger'
        },
        {
          title: 'Available Items',
          value: `${this.dashboardData.availableItems} / ${this.dashboardData.totalItems}`,
          icon: 'fa-tools',
          borderClass: 'border-left-success',
          textClass: 'text-success'
        },
        {
          title: 'Utilization Rate',
          value: `${this.calculateUtilizationRate()}%`,
          icon: 'fa-percentage',
          borderClass: 'border-left-info',
          textClass: 'text-info',
          progress: true,
          progressClass: 'bg-info',
          progressValue: this.calculateUtilizationRate()
        }
      ];
    },
    recentAgreements() {
      return this.dashboardData.recentAgreements || [];
    },
    upcomingReturns() {
      return this.dashboardData.upcomingReturns || [];
    },
    monthlyRevenue() {
      return this.dashboardData.monthlyRevenue || {};
    },
    createAgreementUrl() {
      return this.routes.create;
    },
    agreementsIndexUrl() {
      return this.routes.index;
    }
  },
  mounted() {
    this.initRevenueChart();
  },
  methods: {
    calculateUtilizationRate() {
      if (this.dashboardData.totalItems > 0) {
        return Math.round((this.dashboardData.totalItems - this.dashboardData.availableItems) / this.dashboardData.totalItems * 100);
      }
      return 0;
    },
    formatDate(dateString) {
      const date = new Date(dateString);
      return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    },
    formatShortDate(dateString) {
      const date = new Date(dateString);
      return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    },
    formatNumber(value) {
      return parseFloat(value).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    },
    capitalize(string) {
      return string.charAt(0).toUpperCase() + string.slice(1);
    },
    getStatusClass(status) {
      if (status === 'active') return 'bg-success';
      if (status === 'overdue') return 'bg-danger';
      return 'bg-secondary';
    },
    getReturnDateClass(dateString) {
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      
      const date = new Date(dateString);
      date.setHours(0, 0, 0, 0);
      
      if (date < today) return 'text-danger';
      if (date.getTime() === today.getTime()) return 'text-warning';
      return 'text-muted';
    },
    getAgreementUrl(id) {
      return this.routes.show.replace(':id', id);
    },
    initRevenueChart() {
      const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
      const revenueData = Array(12).fill(0);
      
      // Fill in the data from props
      for (const [month, amount] of Object.entries(this.monthlyRevenue)) {
        revenueData[parseInt(month) - 1] = amount;
      }
      
      const ctx = this.$refs.revenueChart.getContext('2d');
      this.chart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: months,
          datasets: [{
            label: 'Revenue ($)',
            data: revenueData,
            backgroundColor: 'rgba(78, 115, 223, 0.5)',
            borderColor: 'rgba(78, 115, 223, 1)',
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                callback: function(value) {
                  return '$' + value;
                }
              }
            }
          }
        }
      });
    }
  }
}
</script>