from flask import Flask, request, jsonify
import pickle
import networkx as nx
from flask_cors import CORS
from scipy.spatial import KDTree
import heapq  # Import heapq untuk dijkstra_manual
import folium
from math import radians, sin, cos, sqrt, atan2

app = Flask(__name__)
CORS(app)  # Mengizinkan CORS

# Muat graf dari file Pickle
with open('semarang_graph.pkl', 'rb') as f:
    graph = pickle.load(f)

# Buat KD-Tree untuk pencarian node terdekat
nodes, coords = zip(*{node: (data['y'], data['x']) for node, data in graph.nodes(data=True)}.items())
kd_tree = KDTree(coords)

def haversine(lat1, lon1, lat2, lon2):
    R = 6371000  # meter
    lat1, lon1, lat2, lon2 = map(radians, [lat1, lon1, lat2, lon2])

    dlat = lat2 - lat1
    dlon = lon2 - lon1

    a = sin(dlat/2)**2 + cos(lat1)*cos(lat2)*sin(dlon/2)**2
    c = 2 * atan2(sqrt(a), sqrt(1-a))

    return R * c


# Fungsi untuk menemukan node terdekat menggunakan KD-Tree
def find_nearest_node(lat, lon):
    _, idx = kd_tree.query((lat, lon))
    return nodes[idx]

# Implementasi manual Dijkstra
def dijkstra_manual(graph, start):
    # Inisialisasi jarak: set semua jarak ke infinity, kecuali start node
    distances = {node: float('inf') for node in graph.nodes()}
    distances[start] = 0  # Jarak dari start node ke dirinya sendiri adalah 0

    # Priority queue untuk memilih node dengan jarak terpendek
    priority_queue = [(0, start)]

    # Set untuk melacak node yang sudah diproses
    visited = set()

    while priority_queue:
        # Ambil node dengan jarak terpendek dari priority queue
        current_distance, current_node = heapq.heappop(priority_queue)

        # Jika node sudah diproses, lewati
        if current_node in visited:
            continue

        # Tandai node sebagai sudah diproses
        visited.add(current_node)

        # Iterasi melalui semua tetangga dari node saat ini
        for neighbor, edges in graph[current_node].items():
            for edge_key, edge_data in edges.items():
                weight = edge_data.get('length', 1)  # Default weight = 1 jika tidak ada
                distance = current_distance + weight

                # Jika ditemukan jarak yang lebih pendek, perbarui jarak
                if distance < distances[neighbor]:
                    distances[neighbor] = distance
                    heapq.heappush(priority_queue, (distance, neighbor))
    return distances


# Dictionary untuk menyimpan hasil SSSP
sssp_cache = {}

@app.route('/calculate_distance', methods=['POST'])
def calculate_distance():
    data = request.json
    print("Received data:", data)  # Debugging

    user_lat = data.get('user_lat')
    user_lon = data.get('user_lon')
    schools = data.get('schools', [])

    # Periksa apakah user_lat dan user_lon valid
    if user_lat is None or user_lon is None or not schools:
        return jsonify({"error": "Invalid input"}), 400

    # Konversi ke float setelah validasi
    user_lat = float(user_lat)
    user_lon = float(user_lon)

    distances = []

    try:
        # Cari node terdekat untuk origin menggunakan KD-Tree
        user_node = find_nearest_node(user_lat, user_lon)
        print(f"Nearest node to user: {user_node}")  # Debugging

        # Periksa apakah hasil SSSP untuk user_node sudah dihitung sebelumnya
        if user_node not in sssp_cache:
            print(f"Calculating SSSP for User Node: {user_node}")  # Debugging
            sssp_cache[user_node] = dijkstra_manual(graph, user_node)  # Ganti dengan dijkstra_manual

            # Ganti nilai Infinity dengan "No path" di cache
            for node, distance in sssp_cache[user_node].items():
                if distance == float('inf'):
                    sssp_cache[user_node][node] = "No path"

        for school in schools:
            school_lat = float(school['latitude'])
            school_lon = float(school['longitude'])

            school_node = find_nearest_node(school_lat, school_lon)

            # koordinat node
            user_node_lat = graph.nodes[user_node]['y']
            user_node_lon = graph.nodes[user_node]['x']

            school_node_lat = graph.nodes[school_node]['y']
            school_node_lon = graph.nodes[school_node]['x']

            # 1️⃣ haversine user -> node user
            h_user = haversine(user_lat, user_lon, user_node_lat, user_node_lon)

            # 3️⃣ haversine node sekolah -> sekolah
            h_school = haversine(school_node_lat, school_node_lon, school_lat, school_lon)

            graph_distance = sssp_cache[user_node].get(school_node)

            if graph_distance == "No path":
                distances.append({
                    "nama_sekolah": school['nama_sekolah'],
                    "distance": "No path"
                })
            else:
                total_distance = h_user + graph_distance + h_school

                distances.append({
                    "nama_sekolah": school['nama_sekolah'],
                    "distance": round(total_distance / 1000, 2)
                })


        # Debugging: Cetak response yang akan dikirim ke klien
        print("Response to client:", distances)
        return jsonify(distances)

    except Exception as e:
        print("Error calculating distance:", str(e))  # Debugging
        return jsonify({"error": str(e)}), 500
 
 # visualisasi rute   
def dijkstra_with_path(graph, start, end):
    distances = {node: float('inf') for node in graph.nodes()}
    previous = {}
    distances[start] = 0

    pq = [(0, start)]

    while pq:
        current_dist, current_node = heapq.heappop(pq)

        if current_node == end:
            break

        if current_dist > distances[current_node]:
            continue

        for neighbor, edges in graph[current_node].items():
            for _, edge_data in edges.items():
                weight = edge_data.get('length', 1)
                new_dist = current_dist + weight

                if new_dist < distances[neighbor]:
                    distances[neighbor] = new_dist
                    previous[neighbor] = current_node
                    heapq.heappush(pq, (new_dist, neighbor))

    # Rekonstruksi path
    path = []
    node = end
    while node in previous:
        path.append(node)
        node = previous[node]
    path.append(start)
    path.reverse()

    return path
    
    
@app.route('/lihat_rute', methods=['GET'])
def lihat_rute():
    # --- Ambil parameter ---
    user_lat = float(request.args.get('user_lat'))
    user_lon = float(request.args.get('user_lon'))
    school_lat = float(request.args.get('school_lat'))
    school_lon = float(request.args.get('school_lon'))

    school_name = request.args.get('school_name', 'Sekolah Tujuan')
    distance = request.args.get('distance', 'N/A')

    # --- Cari node terdekat ---
    user_node = find_nearest_node(user_lat, user_lon)
    school_node = find_nearest_node(school_lat, school_lon)

    # --- Hitung path antar node ---
    path_nodes = dijkstra_with_path(graph, user_node, school_node)

    path_coords = [
        (graph.nodes[n]['y'], graph.nodes[n]['x'])
        for n in path_nodes
    ]

    # --- Koordinat penting ---
    user_coord = (user_lat, user_lon)
    school_coord = (school_lat, school_lon)

    user_node_coord = (graph.nodes[user_node]['y'], graph.nodes[user_node]['x'])
    school_node_coord = (graph.nodes[school_node]['y'], graph.nodes[school_node]['x'])

    # --- Inisialisasi peta (fokus ke rute) ---
    m = folium.Map(location=user_coord, zoom_start=14)

    # ===============================
    # MARKER (TIDAK REDUNDAN)
    # ===============================
    folium.Marker(
        user_coord,
        popup="Lokasi User",
        icon=folium.Icon(color="green", icon="user", prefix="fa")
    ).add_to(m)

    folium.Marker(
        school_coord,
        popup=f"<b>{school_name}</b><br>Total Jarak: {distance} km",
        icon=folium.Icon(color="blue", icon="graduation-cap", prefix="fa")
    ).add_to(m)

    # ===============================
    # RUTE JALAN (GRAPH - SOLID)
    # ===============================
    folium.PolyLine(
        path_coords,
        color="red",
        weight=5,
        tooltip="Rute Jalan Terpendek (Dijkstra)"
    ).add_to(m)

    # ===============================
    # HAVERSINE (DASHED - WARNA SAMA)
    # ===============================
    folium.PolyLine(
        [user_coord, user_node_coord],
        color="red",
        weight=3,
        dash_array="5,5",
        tooltip="Estimasi Lurus: User → Node Terdekat"
    ).add_to(m)

    folium.PolyLine(
        [school_node_coord, school_coord],
        color="red",
        weight=3,
        dash_array="5,5",
        tooltip="Estimasi Lurus: Node → Sekolah"
    ).add_to(m)
    
    return m._repr_html_()
    

if __name__ == '__main__':
    app.run(debug=True)