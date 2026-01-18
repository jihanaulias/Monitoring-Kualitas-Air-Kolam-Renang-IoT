CREATE TABLE nama_kolam (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL
);

CREATE TABLE measurements (
    id_measurement INT AUTO_INCREMENT PRIMARY KEY,
    id_kolam INT NOT NULL,
    ph_value DECIMAL(4,2),
    tds_value INT,
    temperature_value DECIMAL(5,2),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    statusAir VARCHAR(50),

    CONSTRAINT fk_measurements_kolam
        FOREIGN KEY (id_kolam)
        REFERENCES nama_kolam(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE device_config (
  id INT PRIMARY KEY,
  active_kolam_id INT NOT NULL,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ini harus di-insert dulu
INSERT INTO device_config (id, active_kolam_id)
VALUES (1, 1);
