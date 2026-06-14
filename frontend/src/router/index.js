import { createRouter, createWebHistory } from 'vue-router'
import { ensureActivePinia } from '@/stores'
import { useAuthStore } from '@/stores/auth'

const LandingView = () => import('@/views/LandingView.vue')
const LoginView = () => import('@/views/LoginView.vue')
const RegisterView = () => import('@/views/RegisterView.vue')
const RidesSearchView = () => import('@/views/RidesSearchView.vue')
const RideDetailsView = () => import('@/views/RideDetailsView.vue')
const RideCreateView = () => import('@/views/RideCreateView.vue')
const VehiclesView = () => import('@/views/VehiclesView.vue')
const ProfileView = () => import('@/views/ProfileView.vue')
const MyRidesView = () => import('@/views/MyRidesView.vue')
const MyBookingsView = () => import('@/views/MyBookingsView.vue')
const BookingRequestsView = () => import('@/views/BookingRequestsView.vue')
const RideManagementView = () => import('@/views/RideManagementView.vue')
const EditProfileView = () => import('@/views/EditProfileView.vue')
const ReviewsView = () => import('@/views/ReviewsView.vue')
const BalanceView = () => import('@/views/BalanceView.vue')
const AdminStatsView = () => import('@/views/AdminStatsView.vue')
const AdminBalanceRequestsView = () => import('@/views/AdminBalanceRequestsView.vue')
const AdminDashboardView = () => import('@/views/AdminDashboardView.vue')
const AdminUsersView = () => import('@/views/AdminUsersView.vue')
const AdminRidesView = () => import('@/views/AdminRidesView.vue')
const AdminBookingsView = () => import('@/views/AdminBookingsView.vue')
const AdminVehiclesView = () => import('@/views/AdminVehiclesView.vue')
const AdminVehicleVerificationsView = () => import('@/views/AdminVehicleVerificationsView.vue')
const AdminAuditLogsView = () => import('@/views/AdminAuditLogsView.vue')
const ManagerDashboardView = () => import('@/views/ManagerDashboardView.vue')
const ChatsView = () => import('@/views/ChatsView.vue')
const NotificationsView = () => import('@/views/NotificationsView.vue')

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes: [
        {
            path: '/',
            name: 'landing',
            component: LandingView,
        },
        {
            path: '/login',
            name: 'login',
            component: LoginView,
            meta: { guestOnly: true },
        },
        {
            path: '/register',
            name: 'register',
            component: RegisterView,
            meta: { guestOnly: true },
        },
        {
            path: '/rides',
            name: 'rides.search',
            component: RidesSearchView,
        },
        {
            path: '/rides/:id',
            name: 'rides.show',
            component: RideDetailsView,
        },
        {
            path: '/rides/create',
            name: 'rides.create',
            component: RideCreateView,
            meta: { requiresAuth: true },
        },
        {
            path: '/vehicles',
            name: 'vehicles',
            component: VehiclesView,
            meta: { requiresAuth: true },
        },
        {
            path: '/profile',
            name: 'profile',
            component: ProfileView,
            meta: { requiresAuth: true },
        },
        {
            path: '/balance',
            name: 'balance',
            component: BalanceView,
            meta: { requiresAuth: true },
        },
        {
            path: '/my-rides',
            name: 'my-rides',
            component: MyRidesView,
            meta: { requiresAuth: true },
        },
        {
            path: '/my-bookings',
            name: 'my-bookings',
            component: MyBookingsView,
            meta: { requiresAuth: true },
        },
        {
            path: '/booking-requests',
            name: 'booking-requests',
            component: BookingRequestsView,
            meta: { requiresAuth: true },
        },
        {
            path: '/ride-management',
            name: 'ride-management',
            component: RideManagementView,
            meta: { requiresAuth: true },
        },
        {
            path: '/edit-profile',
            name: 'edit-profile',
            component: EditProfileView,
            meta: { requiresAuth: true },
        },
        {
            path: '/reviews',
            name: 'reviews',
            component: ReviewsView,
            meta: { requiresAuth: true },
        },
        {
            path: '/chats',
            name: 'chats',
            component: ChatsView,
            meta: { requiresAuth: true },
        },
        {
            path: '/notifications',
            name: 'notifications',
            component: NotificationsView,
            meta: { requiresAuth: true },
        },
        {
            path: '/admin',
            name: 'admin.dashboard',
            component: AdminDashboardView,
            meta: { requiresAuth: true, requiresAdmin: true },
        },
        {
            path: '/admin/users',
            name: 'admin.users',
            component: AdminUsersView,
            meta: { requiresAuth: true, requiresAdmin: true },
        },
        {
            path: '/admin/rides',
            name: 'admin.rides',
            component: AdminRidesView,
            meta: { requiresAuth: true, requiresAdmin: true },
        },
        {
            path: '/admin/bookings',
            name: 'admin.bookings',
            component: AdminBookingsView,
            meta: { requiresAuth: true, requiresAdmin: true },
        },
        {
            path: '/admin/vehicles',
            name: 'admin.vehicles',
            component: AdminVehiclesView,
            meta: { requiresAuth: true, requiresAdmin: true },
        },
        {
            path: '/admin/stats',
            name: 'admin.stats',
            component: AdminStatsView,
            meta: { requiresAuth: true, requiresAdmin: true },
        },
        {
            path: '/admin/balance-requests',
            name: 'admin.balance-requests',
            component: AdminBalanceRequestsView,
            meta: { requiresAuth: true, requiresAdmin: true },
        },
        {
            path: '/admin/vehicle-verifications',
            name: 'admin.vehicle-verifications',
            component: AdminVehicleVerificationsView,
            meta: { requiresAuth: true, requiresAdmin: true },
        },
        {
            path: '/admin/audit-logs',
            name: 'admin.audit-logs',
            component: AdminAuditLogsView,
            meta: { requiresAuth: true, requiresAdmin: true },
        },
        {
            path: '/manager',
            name: 'manager.dashboard',
            component: ManagerDashboardView,
            meta: { requiresAuth: true, requiresManager: true },
        },
        {
            path: '/manager/balance-requests',
            name: 'manager.balance-requests',
            component: AdminBalanceRequestsView,
            meta: { requiresAuth: true, requiresManager: true },
        },
        {
            path: '/manager/vehicle-verifications',
            name: 'manager.vehicle-verifications',
            component: AdminVehicleVerificationsView,
            meta: { requiresAuth: true, requiresManager: true },
        },
        {
            path: '/manager/audit-logs',
            name: 'manager.audit-logs',
            component: AdminAuditLogsView,
            meta: { requiresAuth: true, requiresManager: true },
        },
        {
            path: '/:pathMatch(.*)*',
            redirect: '/',
        },
    ],
})

router.beforeEach(async (to, from, next) => {
    ensureActivePinia()
    const auth = useAuthStore()

    if (!auth.initialized) {
        await auth.bootstrap()
    }

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        return next({ name: 'login', query: { redirect: to.fullPath } })
    }

    if (to.meta.requiresAdmin && !auth.isAdmin) {
        return next({ name: 'profile' })
    }

    if (to.meta.requiresManager && !auth.isManager) {
        return next({ name: 'profile' })
    }

    if (to.meta.guestOnly && auth.isAuthenticated) {
        return next({ name: 'profile' })
    }

    return next()
})

export default router
