DROP TABLE IF EXISTS application_languages;
DROP TABLE IF EXISTS programming_languages;
DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS admins;

CREATE TABLE applications (
                              id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                              login VARCHAR(64) NULL,
                              password_hash VARCHAR(255) NULL,
                              fio VARCHAR(150) NOT NULL,
                              phone VARCHAR(30) NOT NULL,
                              email VARCHAR(255) NOT NULL,
                              birth_date DATE NOT NULL,
                              gender ENUM('male', 'female') NOT NULL,
                              biography TEXT NOT NULL,
                              contract_accepted TINYINT(1) NOT NULL,
                              created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                              PRIMARY KEY (id),
                              UNIQUE KEY unique_application_login (login)
);

CREATE TABLE programming_languages (
                                       id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                                       name VARCHAR(100) NOT NULL,
                                       PRIMARY KEY (id),
                                       UNIQUE KEY unique_language_name (name)
);

CREATE TABLE application_languages (
                                       id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                                       application_id INT UNSIGNED NOT NULL,
                                       language_id INT UNSIGNED NOT NULL,
                                       PRIMARY KEY (id),
                                       UNIQUE KEY unique_application_language (application_id, language_id),
                                       CONSTRAINT fk_application_languages_application
                                           FOREIGN KEY (application_id) REFERENCES applications(id)
                                               ON DELETE CASCADE,
                                       CONSTRAINT fk_application_languages_language
                                           FOREIGN KEY (language_id) REFERENCES programming_languages(id)
                                               ON DELETE CASCADE
);

CREATE TABLE admins (
                        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                        login VARCHAR(64) NOT NULL,
                        password_hash VARCHAR(255) NOT NULL,
                        PRIMARY KEY (id),
                        UNIQUE KEY unique_admin_login (login)
);

INSERT INTO programming_languages (name) VALUES
                                             ('Pascal'),
                                             ('C'),
                                             ('C++'),
                                             ('JavaScript'),
                                             ('PHP'),
                                             ('Python'),
                                             ('Java'),
                                             ('Haskell'),
                                             ('Clojure'),
                                             ('Prolog'),
                                             ('Scala'),
                                             ('Go');

INSERT INTO admins (login, password_hash) VALUES
    ('admin', '$2y$12$2m0BYdKw3rhCO5H56AVnkO.bIYkQCQl.UCHFYhNb4X0Nms.gMslPC');