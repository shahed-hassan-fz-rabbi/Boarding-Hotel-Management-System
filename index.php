<?php
/**
 * ==========================================
 * BOARDING SCHOOL MANAGEMENT SYSTEM
 * Central Dashboard - Hostel Room Grid Panel
 * ==========================================
 */

require_once 'includes/auth_check.php'; // Fetch db.php & connection internally
require 'components/header.php';

/* ============================
   Fetch All Hostel Rooms
============================ */
$sql = "SELECT * FROM hostel_rooms ORDER BY room_number ASC";
$result = $conn->query($sql);

$rooms = [];
$totalRooms = 0;
$availableRooms = 0;
$fullRooms = 0;
$totalBeds = 0;
$occupiedBeds = 0;
$availableBeds = 0;

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row['available_beds'] = $row['total_beds'] - $row['allocated_beds'];
        $row['occupancy_percent'] = $row['total_beds'] > 0 ? round(($row['allocated_beds'] / $row['total_beds']) * 100) : 0;
        
        $rooms[] = $row;
        
        $totalRooms++;
        if ($row['status'] == 'Available') {
            $availableRooms++;
        } else {
            $fullRooms++;
        }
        
        $totalBeds += $row['total_beds'];
        $occupiedBeds += $row['allocated_beds'];
        $availableBeds += $row['available_beds'];
    }
}

$roomsJSON = json_encode($rooms);
?>

<style>
    .card-room {
        animation: fadeInUp 0.4s ease-out forwards;
        opacity: 0;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(15px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #4f46e5 0%, #6366f1 100%);
        border-radius: 20px;
        transition: width 0.8s ease;
    }

    .badge-available {
        background: #dcfce7;
        color: #166534;
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .badge-full {
        background: #fee2e2;
        color: #991b1b;
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .room-type-badge {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.25rem 0.5rem;
        border-radius: 8px;
        display: inline-block;
    }

    .room-type-boys { background: #dbeafe; color: #1e40af; }
    .room-type-girls { background: #fce7f3; color: #be185d; }
    .room-type-staff { background: #fef3c7; color: #b45309; }
</style>

<div class="max-w-[1600px] mx-auto space-y-6">

    <!-- 6 Professional Summary Statistics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="bg-white border border-slate-200 p-4 rounded-2xl text-center shadow-sm">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Rooms</p>
            <h3 class="text-2xl font-black text-slate-800 mt-1"><?= $totalRooms ?></h3>
        </div>
        <div class="bg-white border border-slate-200 p-4 rounded-2xl text-center shadow-sm">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Available Rooms</p>
            <h3 class="text-2xl font-black text-emerald-600 mt-1"><?= $availableRooms ?></h3>
        </div>
        <div class="bg-white border border-slate-200 p-4 rounded-2xl text-center shadow-sm">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Full Rooms</p>
            <h3 class="text-2xl font-black text-rose-600 mt-1"><?= $fullRooms ?></h3>
        </div>
        <div class="bg-white border border-slate-200 p-4 rounded-2xl text-center shadow-sm">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Beds</p>
            <h3 class="text-2xl font-black text-amber-500 mt-1"><?= $totalBeds ?></h3>
        </div>
        <div class="bg-white border border-slate-200 p-4 rounded-2xl text-center shadow-sm">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Occupied Beds</p>
            <h3 class="text-2xl font-black text-indigo-600 mt-1"><?= $occupiedBeds ?></h3>
        </div>
        <div class="bg-white border border-slate-200 p-4 rounded-2xl text-center shadow-sm">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Available Beds</p>
            <h3 class="text-2xl font-black text-cyan-600 mt-1"><?= $availableBeds ?></h3>
        </div>
    </div>

    <!-- Toolbar Filters Layout -->
    <div class="bg-white border border-slate-200 rounded-2xl p-4 flex flex-col md:flex-row gap-4 no-print">
        <input type="text" id="searchInput" placeholder="🔍 Search by Room Number..." class="border border-slate-200 bg-slate-50/50 p-2.5 rounded-xl outline-none focus:bg-white focus:border-indigo-500 transition flex-1 text-sm font-medium">
        
        <select id="filterType" class="border border-slate-200 bg-slate-50/50 p-2.5 rounded-xl outline-none focus:bg-white focus:border-indigo-500 transition text-sm font-medium cursor-pointer">
            <option value="">All Room Wings</option>
            <option value="Boys">Boys Wing</option>
            <option value="Girls">Girls Wing</option>
            <option value="Staff">Staff Wing</option>
        </select>

        <select id="sortRooms" class="border border-slate-200 bg-slate-50/50 p-2.5 rounded-xl outline-none focus:bg-white focus:border-indigo-500 transition text-sm font-medium cursor-pointer">
            <option value="asc">Sort: Room Number (Asc)</option>
            <option value="desc">Sort: Room Number (Desc)</option>
            <option value="available">Most Available Beds</option>
            <option value="occupied">Most Occupied Beds</option>
        </select>
    </div>

    <!-- Room Layout Grid -->
    <div id="roomContainer" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        <!-- Cards Injected Dynamically via JS -->
    </div>

    <!-- Zero State Box -->
    <div id="noResults" class="hidden bg-white border border-slate-200 rounded-2xl p-12 text-center text-slate-400 font-medium">
        🔍 No rooms match your search or filter parameters.
    </div>

</div>

<script>
    const allRooms = <?php echo $roomsJSON; ?>;
    
    const searchInput = document.getElementById('searchInput');
    const filterType = document.getElementById('filterType');
    const sortRooms = document.getElementById('sortRooms');
    const roomContainer = document.getElementById('roomContainer');
    const noResults = document.getElementById('noResults');

    function renderRooms(rooms) {
        roomContainer.innerHTML = '';

        if (rooms.length === 0) {
            noResults.classList.remove('hidden');
            return;
        }
        noResults.classList.add('hidden');

        rooms.forEach((room, index) => {
            const badgeClass = room.status === 'Available' ? 'badge-available' : 'badge-full';
            const badgeText = room.status === 'Available' ? '✓ Available' : '✗ Full';
            const roomTypeClass = `room-type-${room.room_type.toLowerCase()}`;

            const card = document.createElement('div');
            card.className = 'bg-white border border-slate-200 card-room rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition';
            card.style.animationDelay = `${index * 0.02}s`;
            card.innerHTML = `
                <div class="p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-xl font-bold text-slate-800">${room.room_number}</h3>
                            <span class="room-type-badge mt-1.5 ${roomTypeClass}">${room.room_type} Wing</span>
                        </div>
                        <span class="${badgeClass}">${badgeText}</span>
                    </div>

                    <p class="text-slate-500 text-xs font-medium mb-4 italic truncate">${room.description || 'No description available.'}</p>

                    <div class="grid grid-cols-3 gap-2.5 mb-4">
                        <div class="bg-slate-50 border border-slate-100 rounded-xl p-2 text-center">
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Total</p>
                            <p class="text-md font-bold text-slate-700">${room.total_beds}</p>
                        </div>
                        <div class="bg-slate-50 border border-slate-100 rounded-xl p-2 text-center">
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Occupied</p>
                            <p class="text-md font-bold text-orange-600">${room.allocated_beds}</p>
                        </div>
                        <div class="bg-slate-50 border border-slate-100 rounded-xl p-2 text-center">
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Vacant</p>
                            <p class="text-md font-bold text-emerald-600">${room.available_beds}</p>
                        </div>
                    </div>

                    <div class="mb-1">
                        <div class="flex justify-between items-center mb-1">
                            <p class="text-xs font-bold text-slate-500">Occupancy Rate</p>
                            <p class="text-xs font-black text-slate-700">${room.occupancy_percent}%</p>
                        </div>
                        <div class="bg-slate-100 rounded-full h-2 overflow-hidden">
                            <div class="progress-fill" style="width: ${room.occupancy_percent}%"></div>
                        </div>
                    </div>

                    <div class="mt-4 pt-2">
                        <a href="allocations/list.php" class="w-full bg-slate-50 border border-slate-200 text-slate-700 py-2 rounded-xl font-bold text-xs text-center block hover:bg-indigo-600 hover:text-white hover:border-indigo-600 transition">
                            Open Room Registry →
                        </a>
                    </div>
                </div>
            `;
            roomContainer.appendChild(card);
        });
    }

    function applyFiltersAndSort() {
        let result = [...allRooms];
        const searchTerm = searchInput.value.toLowerCase().trim();
        const filterValue = filterType.value;
        const sortValue = sortRooms.value;

        if (searchTerm) {
            result = result.filter(room => room.room_number.toLowerCase().includes(searchTerm));
        }

        if (filterValue) {
            result = result.filter(room => room.room_type === filterValue);
        }

        if (sortValue === 'asc') {
            result.sort((a, b) => a.room_number.localeCompare(b.room_number));
        } else if (sortValue === 'desc') {
            result.sort((a, b) => b.room_number.localeCompare(a.room_number));
        } else if (sortValue === 'available') {
            result.sort((a, b) => b.available_beds - a.available_beds);
        } else if (sortValue === 'occupied') {
            result.sort((a, b) => b.allocated_beds - a.allocated_beds);
        }

        renderRooms(result);
    }

    searchInput.addEventListener('input', applyFiltersAndSort);
    filterType.addEventListener('change', applyFiltersAndSort);
    sortRooms.addEventListener('change', applyFiltersAndSort);

    // Bootstrap execution
    renderRooms(allRooms);
</script>

<?php
require 'components/footer.php'; 
?>