<template>
  <div>
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Rental Items</h5>
        <div>
          <div class="input-group">
            <input 
              type="text" 
              class="form-control" 
              placeholder="Search items..." 
              v-model="searchQuery"
              @input="handleSearch"
            >
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
              Filter
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="#" @click.prevent="setFilter('all')">All</a></li>
              <li><a class="dropdown-item" href="#" @click.prevent="setFilter('available')">Available</a></li>
              <li><a class="dropdown-item" href="#" @click.prevent="setFilter('rented')">Rented</a></li>
              <li><a class="dropdown-item" href="#" @click.prevent="setFilter('maintenance')">Maintenance</a></li>
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
                <th @click="sort('name')">
                  Name
                  <i v-if="sortColumn === 'name'" class="fas" :class="sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down'"></i>
                </th>
                <th @click="sort('category')">
                  Category
                  <i v-if="sortColumn === 'category'" class="fas" :class="sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down'"></i>
                </th>
                <th @click="sort('daily_rate')">
                  Daily Rate
                  <i v-if="sortColumn === 'daily_rate'" class="fas" :class="sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down'"></i>
                </th>
                <th @click="sort('status')">
                  Status
                  <i v-if="sortColumn === 'status'" class="fas" :class="sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down'"></i>
                </th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in paginatedItems" :key="item.id">
                <td>{{ item.id }}</td>
                <td>
                  <div class="d-flex align-items-center">
                    <div v-if="item.image" class="me-2">
                      <img :src="item.image" alt="Item" class="rounded" width="40" height="40">
                    </div>
                    <div>
                      <div class="fw-bold">{{ item.name }}</div>
                      <div class="small text-muted">{{ item.serial_number }}</div>
                    </div>
                  </div>
                </td>
                <td>{{ item.category }}</td>
                <td>${{ formatNumber(item.daily_rate) }}</td>
                <td>
                  <span class="badge" :class="getStatusClass(item.status)">
                    {{ capitalize(item.status) }}
                  </span>
                </td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <a :href="getItemUrl(item.id)" class="btn btn-outline-primary">
                      <i class="fas fa-eye"></i>
                    </a>
                    <a :href="getEditUrl(item.id)" class="btn btn-outline-secondary">
                      <i class="fas fa-edit"></i>
                    </a>
                    <button 
                      v-if="item.status === 'available'" 
                      class="btn btn-outline-success" 
                      @click="quickRent(item.id)"
                    >
                      <i class="fas fa-handshake"></i>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="paginatedItems.length === 0">
                <td colspan="6" class="text-center py-3">No rental items found</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-between align-items-center">
        <div>
          Showing {{ paginatedItems.length }} of {{ filteredItems.length }} items
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
    items: {
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
    filteredItems() {
      let result = [...this.items];
      
      // Apply filter
      if (this.currentFilter !== 'all') {
        result = result.filter(item => item.status === this.currentFilter);
      }
      
      // Apply search
      if (this.searchQuery.trim() !== '') {
        const query = this.searchQuery.toLowerCase();
        result = result.filter(item => 
          item.id.toString().includes(query) ||
          item.name.toLowerCase().includes(query) ||
          (item.serial_number && item.serial_number.toLowerCase().includes(query)) ||
          (item.category && item.category.toLowerCase().includes(query))
        );
      }
      
      // Apply sorting
      result.sort((a, b) => {
        let valueA = a[this.sortColumn];
        let valueB = b[this.sortColumn];
        
        // Handle numeric values
        if (this.sortColumn === 'daily_rate' || this.sortColumn === 'id') {
          valueA = parseFloat(valueA);
          valueB = parseFloat(valueB);
        } else if (valueA && valueB) {
          valueA = valueA.toString().toLowerCase();
          valueB = valueB.toString().toLowerCase();
        }
        
        if (valueA < valueB) return this.sortDirection === 'asc' ? -1 : 1;
        if (valueA > valueB) return this.sortDirection === 'asc' ? 1 : -1;
        return 0;
      });
      
      return result;
    },
    paginatedItems() {
      const start = (this.currentPage - 1) * this.itemsPerPage;
      const end = start + this.itemsPerPage;
      return this.filteredItems.slice(start, end);
    },
    totalPages() {
      return Math.ceil(this.filteredItems.length / this.itemsPerPage);
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
    formatNumber(value) {
      return parseFloat(value).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    },
    capitalize(string) {
      return string.charAt(0).toUpperCase() + string.slice(1);
    },
    getStatusClass(status) {
      if (status === 'available') return 'bg-success';
      if (status === 'rented') return 'bg-primary';
      if (status === 'maintenance') return 'bg-warning';
      return 'bg-secondary';
    },
    getItemUrl(id) {
      return this.routes.show.replace(':id', id);
    },
    getEditUrl(id) {
      return this.routes.edit.replace(':id', id);
    },
    quickRent(id) {
      // Emit event to parent component or redirect to quick rent page
      if (this.routes.quickRent) {
        window.location.href = this.routes.quickRent.replace(':id', id);
      } else {
        this.$emit('quick-rent', id);
      }
    }
  }
}
</script>

