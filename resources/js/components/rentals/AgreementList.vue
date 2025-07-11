<template>
  <div>
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Rental Agreements</h5>
        <div>
          <div class="input-group">
            <input 
              type="text" 
              class="form-control" 
              placeholder="Search agreements..." 
              v-model="searchQuery"
              @input="handleSearch"
            >
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
              Filter
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="#" @click.prevent="setFilter('all')">All</a></li>
              <li><a class="dropdown-item" href="#" @click.prevent="setFilter('active')">Active</a></li>
              <li><a class="dropdown-item" href="#" @click.prevent="setFilter('completed')">Completed</a></li>
              <li><a class="dropdown-item" href="#" @click.prevent="setFilter('overdue')">Overdue</a></li>
            </ul>
          </div>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th @click="sort('id')">
                  ID
                  <i v-if="sortColumn === 'id'" class="fas" :class="sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down'"></i>
                </th>
                <th @click="sort('customer_name')">
                  Customer
                  <i v-if="sortColumn === 'customer_name'" class="fas" :class="sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down'"></i>
                </th>
                <th @click="sort('start_date')">
                  Start Date
                  <i v-if="sortColumn === 'start_date'" class="fas" :class="sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down'"></i>
                </th>
                <th @click="sort('end_date')">
                  End Date
                  <i v-if="sortColumn === 'end_date'" class="fas" :class="sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down'"></i>
                </th>
                <th @click="sort('status')">
                  Status
                  <i v-if="sortColumn === 'status'" class="fas" :class="sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down'"></i>
                </th>
                <th @click="sort('total_amount')">
                  Amount
                  <i v-if="sortColumn === 'total_amount'" class="fas" :class="sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down'"></i>
                </th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="agreement in filteredAgreements" :key="agreement.id">
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
                <td>
                  <div class="btn-group btn-group-sm">
                    <a :href="getAgreementUrl(agreement.id)" class="btn btn-outline-primary">
                      <i class="fas fa-eye"></i>
                    </a>
                    <a :href="getEditUrl(agreement.id)" class="btn btn-outline-secondary">
                      <i class="fas fa-edit"></i>
                    </a>
                    <a :href="getPdfUrl(agreement.id)" class="btn btn-outline-info" target="_blank">
                      <i class="fas fa-file-pdf"></i>
                    </a>
                  </div>
                </td>
              </tr>
              <tr v-if="filteredAgreements.length === 0">
                <td colspan="7" class="text-center py-3">No rental agreements found</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-between align-items-center">
        <div>
          Showing {{ filteredAgreements.length }} of {{ agreements.length }} agreements
        </div>
        <nav v-if="totalPages > 1">
          <ul class="pagination mb-0">
            <li class="page-item" :class="{ disabled: currentPage === 1 }">
              <a class="page-link" href="#" @click.prevent="changePage(currentPage - 1)">Previous</a>
            </li>
            <li v-for="page in paginationRange" :key="page" class="page-item" :class="{ active: currentPage === page }">
              <a class="page-link" href="#" @click.prevent="changePage(page)">{{ page }}</a>
            </li>
            <li class="page-item" :class="{ disabled: currentPage === totalPages }">
              <a class="page-link" href="#" @click.prevent="changePage(currentPage + 1)">Next</a>
            </li>
          </ul>
        </nav>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    agreements: {
      type: Array,
      required: true
    },
    routes: {
      type: Object,
      required: true
    }
  },
  data() {
    return {
      searchQuery: '',
      currentFilter: 'all',
      sortColumn: 'id',
      sortDirection: 'desc',
      currentPage: 1,
      itemsPerPage: 10
    };
  },
  computed: {
    filteredAgreements() {
      let result = [...this.agreements];
      
      // Apply filter
      if (this.currentFilter !== 'all') {
        result = result.filter(agreement => agreement.status === this.currentFilter);
      }
      
      // Apply search
      if (this.searchQuery.trim() !== '') {
        const query = this.searchQuery.toLowerCase();
        result = result.filter(agreement => 
          agreement.id.toString().includes(query) ||
          agreement.customer_name.toLowerCase().includes(query)
        );
      }
      
      // Apply sorting
      result.sort((a, b) => {
        let valueA = a[this.sortColumn];
        let valueB = b[this.sortColumn];
        
        // Handle dates
        if (this.sortColumn === 'start_date' || this.sortColumn === 'end_date') {
          valueA = new Date(valueA);
          valueB = new Date(valueB);
        }
        
        if (valueA < valueB) return this.sortDirection === 'asc' ? -1 : 1;
        if (valueA > valueB) return this.sortDirection === 'asc' ? 1 : -1;
        return 0;
      });
      
      return result;
    },
    paginatedAgreements() {
      const start = (this.currentPage - 1) * this.itemsPerPage;
      const end = start + this.itemsPerPage;
      return this.filteredAgreements.slice(start, end);
    },
    totalPages() {
      return Math.ceil(this.filteredAgreements.length / this.itemsPerPage);
    },
    paginationRange() {
      const range = [];
      const maxVisiblePages = 5;
      
      let startPage = Math.max(1, this.currentPage - Math.floor(maxVisiblePages / 2));
      let endPage = startPage + maxVisiblePages - 1;
      
      if (endPage > this.totalPages) {
        endPage = this.totalPages;
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
      }
      
      for (let i = startPage; i <= endPage; i++) {
        range.push(i);
      }
      
      return range;
    }
  },
  methods: {
    handleSearch() {
      this.currentPage = 1;
    },
    setFilter(filter) {
      this.currentFilter = filter;
      this.currentPage = 1;
    },
    sort(column) {
      if (this.sortColumn === column) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortColumn = column;
        this.sortDirection = 'asc';
      }
    },
    changePage(page) {
      if (page >= 1 && page <= this.totalPages) {
        this.currentPage = page;
      }
    },
    formatDate(dateString) {
      const date = new Date(dateString);
      return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
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
      if (status === 'completed') return 'bg-secondary';
      return 'bg-info';
    },
    getAgreementUrl(id) {
      return this.routes.show.replace(':id', id);
    },
    getEditUrl(id) {
      return this.routes.edit.replace(':id', id);
    },
    getPdfUrl(id) {
      return this.routes.pdf.replace(':id', id);
    }
  }
}
</script>