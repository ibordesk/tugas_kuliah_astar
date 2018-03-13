<?php
/**
 * Remake from Hisune.com
 */
 
$config = array(
    'start' => array(1, 1), // koordinat asal
    'end' => array(18, 17), // kordinat  tujuan
    'x' => 18, // jumlah maksimal sumbu atas
    'y' => 17, // jumlah maksimal sumbu bawah
    'disable_num' => 100, // jumlah hambatan
    'anotherpoint'=>array(
        array(array(17,4),array(17,5),array(16,4),array(16,5)), // area J
        array(array(14,9),array(15,9),array(14,8),array(15,8),),// area K
        array(array(7,1),array(7,2),array(8,1),array(8,2),),// area L
        array(array(4,16),array(4,15),array(3,15),array(3,16),),// area M
        array(array(10,10),array(10,11),array(11,10),array(11,11),),// area N
    )
);
 
$a = new aStar(
    $config['start'], 
    $config['end'], 
    $config['x'], 
    $config['y'], 
    $config['disable_num'], 
    $config['anotherpoint']
);
$a->displayPic();
 
 
/**
 * pencarian jalur menggunakan astar
 */
class aStar
{

    private $_start; // titik awal
    private $_end; // titik akhir
    private $_x; // sumbu x maksimum
    private $_y; // sumbu y maksimum
    private $_num; // jumlah hambatan
    private $_anotherpoint;
 
    private $_around; // Sebuah array empat node yang mungkin dari node saat ini (atas, kanan, kiri, bawah)
    private $_g; // nilai array
 
    public $open; // var membuka array simpul
    public $close; // var menutup array simpul
    public $disable = array(); // blok rintangan yang dihasilkan secara acak (blok hitam)
    public $route = array(); // hasil array jalur
 
    /**
     * @param $start array titik awal
     * @param $end array titik akhir
     * @param $x int maks sumbu x
     * @param $y int maks sumbu y
     * @param $num int jumlah hambatan
     */
    public function __construct($start, $end, $x, $y, $num, $anotherpoint)
    {
        $this->_start = $start;
        $this->_end = $end;
        $this->_x = $x;
        $this->_y = $y;
        $this->_num = $num;
        $this->_anotherpoint = $anotherpoint; 
        // memanggil fungsi jalur
        $this->_route();
    }
 
    private function _route()
    {
        // memanggil fungsi makeDisable yang bertugas untuk membuat blok batas (blok dgn warna hitam)
        $this->_makeDisable();
        // menjalankan algoritma A*
        $this->_start();
    }
 
    private function _start()
    {
        // tetapkan nilai awal
        $point[0] = $this->_start[0]; // x
        $point[1] = $this->_start[1]; // y
        $point['i'] = $this->_pointInfo($this->_start); // tettapkan node saat ini
        $point['f'] = 0; // nilai f
        $this->_g[$point['i']] = 0; // nilai g
        $point['h'] = $this->_getH($this->_start); // nilai h
        $point['p'] = null; // simpul induk 
        $this->open[$point['i']] = $this->close[$point['i']] = $point; // simpul kepala dan ekor digabungkan
        while (count($this->open) > 0) {
            // menemukan nilai f terkecil
            $f = 0;
            foreach ($this->open as $info => $node) {
                if ($f === 0 || $f > $node['f']) {
                    $minInfo = $info;
                    $f = $node['f'];
                }
            }
 
            $current = $this->open[$minInfo]; // node saat ini dari kepala
            unset($this->open[$minInfo]); //hapus nilai terkecil
            $this->close[$minInfo] = $current; // gabungkan nilai saat ini dengan simpul ekor 
            // jika mencapai titik akhir, hitung sesuai dengan simpul induk masing masing rute
            if ($current[0] == $this->_end[0] && $current[1] == $this->_end[1]) {
                // lakukan reverse [rute terbalik]
                while ($current['p'] !== null) {
                    $tmp = $this->close[$this->_pointInfo($current['p'])];
                    array_unshift($this->route, array($tmp[0], $tmp[1]));
                    $current = $this->close[$this->_pointInfo($current['p'])];
                }
                array_push($this->route, $this->_end);
                break;
            }
            $this->_setAround($current);// tetapkan simpul disekitar simpul saat ini            
            $this->_updateAround($current);// update status simpul disekitar node
        }
    }
 
    private function _updateAround($current)
    {
        foreach ($this->_around as $v) {
            if (!isset($this->close[$this->_pointInfo($v)])) { // tidak sama sekali close hanya menangani di dalam
                if (isset($this->open[$this->_pointInfo($v)])) { // di open, bandingkan nilai, lalu update bila kecil
                    if ($this->_getG($current) < $this->_g[$this->_pointInfo($v)]) {
                        $this->_updatePointDetail($current, $v);
                    }
                } else {// jika bukan di open,update langsung nilainya
                    $this->open[$this->_pointInfo($v)][0] = $v[0];
                    $this->open[$this->_pointInfo($v)][1] = $v[1];
                    $this->_updatePointDetail($current, $v);
                }
            }
        }
    }
 
    private function _updatePointDetail($current, $around)
    {
        $this->open[$this->_pointInfo($around)]['f'] = $this->_getF($current, $around);
        $this->_g[$this->_pointInfo($around)] = $this->_getG($current);
        $this->open[$this->_pointInfo($around)]['h'] = $this->_getH($around);
        $this->open[$this->_pointInfo($around)]['p'] = $current; // mengatur ulang simpul induk
    }
 
    /**
     * mengembalikan simpul saat ini
     */
    private function _setAround($point)
    {
        // kemungkinan poin x
        $roundX[] = $point[0]; // titik x saat ini
        ($point[0] - 1 > 0) && $roundX[] = $point[0] - 1; // tidak melewati batas minimum
        ($point[0] + 1 <= $this->_x) && $roundX[] = $point[0] + 1; // jangan melewati  batas maksimal
        // kemungkinan poin y
        $roundY[] = $point[1];
        ($point[1] - 1 > 0) && $roundY[] = $point[1] - 1;
        ($point[1] + 1 <= $this->_y) && $roundY[] = $point[1] + 1;
 
        $this->_around = array();
        foreach ($roundX as $vX) {
            foreach ($roundY as $vY) {
                $tmp = array(
                    0 => $vX,
                    1 => $vY,
                );
                // tidak di dalam titik rintangan, bukan didalam simpul yg tertutup, bukan dirinya sendiri, bukan diagonal
                if (
                    !in_array($tmp, $this->disable) &&
                    !in_array($tmp, $this->close) &&
                    !($vX == $point[0] && $vY == $point[1]) &&
                    ($vX == $point[0] || $vY == $point[1])
                )
                    $this->_around[] = $tmp;
            }
        }
    }
 
    /**
     * hanya simpul saat ini yang dikembalikan 一key
     */
    private function _pointInfo($point)
    {
        return $point[0] . '_' . $point[1];
    }
 
    /**
     * Perhitungan nilai F: F = G + H
     */
    private function _getF($parent, $point)
    {
        return $this->_getG($parent) + $this->_getH($point);
    }
 
    /**
     * perhitungan nilai G
     */
    private function _getG($current)
    {
        return isset($this->_g[$this->_pointInfo($current)]) ? $this->_g[$this->_pointInfo($current)] + 1 : 1;
    }
 
    /**
     * perhitungan nilai H
     */
    private function _getH($point)
    {
        return abs($point[0] - $this->_end[0]) + abs($point[1] - $this->_end[1]);
    }
 
    /**
     * membuat blok yang menjadi dinding rute secara acak
     */
    private function _makeDisable()
    {
        if ($this->_num > $this->_x * $this->_y)
            exit('too many disable point');
 
        for ($i = 0; $i < $this->_num; $i++) {
            $tmp = array(
                rand(1, $this->_x),
                rand(1, $this->_y),
            );
            if ($tmp == $this->_start || $tmp == $this->_end || in_array($tmp, $this->disable) || in_array($tmp,$this->_anotherpoint[0])|| in_array($tmp,$this->_anotherpoint[1])|| in_array($tmp,$this->_anotherpoint[2])|| in_array($tmp,$this->_anotherpoint[3])|| in_array($tmp,$this->_anotherpoint[4])) { // Poin penghalang jalan tidak bisa sama dengan titik awal dan akhir area huruf, dan yang telah didisable (agar tidak terjadi looping ulang)
                $i--;
                continue;
            }
            $this->disable[] = $tmp;
        }
    }
 
    /**
     * menampilkan hasil secara visual
     */
    public function displayPic()
    {
        $step = count($this->route);
        echo ($step > 0) ? '<font color="green">jumlah blok yang dilalui ' . $step . ' blok</font>' : '<font color="red">data tidak sesuai！</font>';
        echo '<table border="1">';
        for ($y = 1; $y <= $this->_y; $y++) {
            echo '<tr>';
            for ($x = 1; $x <= $this->_x; $x++) {
                $current = array($x, $y);
                if (in_array($current, $this->disable)) // warna hitam menunjukan batas / hambatan
                    {$bg = 'bgcolor="#000"';}
                elseif (in_array($current, $this->route)) // jalur terpendek ditunjukan warna  hijau
                    {$bg = 'bgcolor="#5cb85c"';}
                else
                    {$bg = '';}
 
                if ($current == $this->_start)
                    $content = 'A';
                elseif ($current == $this->_end)
                    $content = 'B';
                elseif (in_array($current,$this->_anotherpoint[0]))
                    $content = 'J';
                elseif (in_array($current,$this->_anotherpoint[1]))
                    $content = 'K';
                elseif (in_array($current,$this->_anotherpoint[2]))
                    $content = 'L';
                elseif (in_array($current,$this->_anotherpoint[3]))
                    $content = 'M';
                elseif (in_array($current,$this->_anotherpoint[4]))
                    $content = 'N';
                else
                    $content = '&nbsp;';
 
                echo '<td style="width:30px; height: 30px;" ' . $bg . '>' . $content . '</td>';
            }
            echo '</tr>';
        }
        echo '</table>';
    }
 
}
