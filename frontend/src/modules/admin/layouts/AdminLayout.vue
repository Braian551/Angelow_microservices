<template>
  <div class="admin-container" :class="{ 'sidebar-collapsed': sidebarCollapsed }">
    <AdminSidebar
      :collapsed="sidebarCollapsed"
      :user="user"
      @toggle="toggleSidebar"
      @close="sidebarCollapsed = true"
    />
    <main class="admin-content">
      <AdminHeader
        @toggle-sidebar="toggleSidebar"
      />
      <div class="dashboard-content">
        <RouterView />
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { RouterView } from 'vue-router'
import { useSession } from '../../../composables/useSession'
import AdminSidebar from '../components/AdminSidebar.vue'
import AdminHeader from '../components/AdminHeader.vue'
import '../styles/admin.css'

const { user } = useSession()
const sidebarCollapsed = ref(false)

function toggleSidebar() {
  sidebarCollapsed.value = !sidebarCollapsed.value
}
</script>
