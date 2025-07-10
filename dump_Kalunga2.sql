--
-- PostgreSQL database dump
--

-- Dumped from database version 14.14 (Homebrew)
-- Dumped by pg_dump version 14.14 (Homebrew)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: Kalunga; Type: SCHEMA; Schema: -; Owner: -
--

CREATE SCHEMA "Kalunga";


--
-- Name: aggiorna_disponibilita_fornitore(); Type: FUNCTION; Schema: Kalunga; Owner: -
--

CREATE FUNCTION "Kalunga".aggiorna_disponibilita_fornitore() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    UPDATE fornitore_prodotto
    SET disponibilita = disponibilita - NEW.quantita
    WHERE fornitore = NEW.fornitore
      AND prodotto = NEW.prodotto;

    RETURN NEW;
END;
$$;


--
-- Name: applica_sconto_scontrino(integer, integer, numeric); Type: FUNCTION; Schema: Kalunga; Owner: -
--

CREATE FUNCTION "Kalunga".applica_sconto_scontrino(p_scontrino_id integer, p_perc_sconto integer, p_totale numeric) RETURNS void
    LANGUAGE plpgsql
    AS $$
DECLARE
    tessera_id INT;
    saldo INT;
    punti_usati INT := 0;
    sconto_valore NUMERIC(10,2);
    nuovo_saldo INT;
BEGIN
    -- recupera tessera
    SELECT tessera INTO tessera_id
    FROM scontrino
    WHERE id = p_scontrino_id;

    -- se non ha tessera esce
    IF tessera_id IS NULL THEN
        UPDATE scontrino
        SET sconto_percentuale = 0, totale_pagato = p_totale
        WHERE id = p_scontrino_id;
        RETURN;
    END IF;

    SELECT saldo_punti INTO saldo FROM tessera WHERE id = tessera_id;

    -- decide se può usare punti
    IF p_perc_sconto = 30 AND saldo >= 300 THEN
        punti_usati := 300;
    ELSIF p_perc_sconto = 15 AND saldo >= 200 THEN
        punti_usati := 200;
    ELSIF p_perc_sconto = 5 AND saldo >= 100 THEN
        punti_usati := 100;
    ELSE
        p_perc_sconto := 0;
    END IF;

    -- calcola nuovo totale
    IF p_perc_sconto > 0 THEN
        sconto_valore := p_totale * (p_perc_sconto / 100.0);
        IF sconto_valore > 100 THEN
            sconto_valore := 100;
        END IF;
        p_totale := p_totale - sconto_valore;

        saldo := saldo - punti_usati;
    END IF;

    -- aggiorna scontrino
    UPDATE scontrino
    SET totale_pagato = p_totale,
        sconto_percentuale = p_perc_sconto
    WHERE id = p_scontrino_id;

    -- aggiunge punti guadagnati
    saldo := saldo + FLOOR(p_totale);

    UPDATE tessera
    SET saldo_punti = saldo
    WHERE id = tessera_id;
END;
$$;


--
-- Name: check_coerenza_scontrino(); Type: FUNCTION; Schema: Kalunga; Owner: -
--

CREATE FUNCTION "Kalunga".check_coerenza_scontrino() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    proprietario INT;
BEGIN
    -- se lo scontrino non ha tessera, tutto ok
    IF NEW.tessera IS NULL THEN
        RETURN NEW;
    END IF;

    -- recupera utente proprietario della tessera
    SELECT utente INTO proprietario
    FROM tessera
    WHERE id = NEW.tessera;

    -- controlla se combacia
    IF proprietario IS NULL OR proprietario <> NEW.utente THEN
        RAISE EXCEPTION 'Utente % non è proprietario della tessera %', NEW.utente, NEW.tessera;
    END IF;

    RETURN NEW;
END;
$$;


--
-- Name: check_modifica_tipo_utente(); Type: FUNCTION; Schema: Kalunga; Owner: -
--

CREATE FUNCTION "Kalunga".check_modifica_tipo_utente() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF OLD.tipo = 'gestore' AND NEW.tipo = 'cliente' THEN
        IF EXISTS (
            SELECT 1 FROM negozio
            WHERE responsabile = NEW.id
        ) THEN
            RAISE EXCEPTION 'Impossibile cambiare tipo utente: è responsabile di un negozio';
        END IF;
    END IF;
    RETURN NEW;
END;
$$;


--
-- Name: check_negozio_non_eliminato_approvv(); Type: FUNCTION; Schema: Kalunga; Owner: -
--

CREATE FUNCTION "Kalunga".check_negozio_non_eliminato_approvv() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF EXISTS (
        SELECT 1
        FROM negozio
        WHERE id = NEW.negozio
          AND eliminato = TRUE
    ) THEN
        RAISE EXCEPTION 'Impossibile effettuare un approvvigionamento: il negozio % è eliminato', NEW.negozio;
    END IF;

    RETURN NEW;
END;
$$;


--
-- Name: check_negozio_non_eliminato_scontrino(); Type: FUNCTION; Schema: Kalunga; Owner: -
--

CREATE FUNCTION "Kalunga".check_negozio_non_eliminato_scontrino() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM negozio
        WHERE id = NEW.negozio AND eliminato = TRUE
    ) THEN
        RAISE EXCEPTION 'Non è possibile registrare uno scontrino per un negozio eliminato';
    END IF;
    RETURN NEW;
END;
$$;


--
-- Name: check_negozio_non_eliminato_tessera(); Type: FUNCTION; Schema: Kalunga; Owner: -
--

CREATE FUNCTION "Kalunga".check_negozio_non_eliminato_tessera() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM negozio
        WHERE id = NEW.negozio AND eliminato = TRUE
    ) THEN
        RAISE EXCEPTION 'Non è possibile emettere una tessera per un negozio eliminato';
    END IF;
    RETURN NEW;
END;
$$;


--
-- Name: check_responsabile_gestore(); Type: FUNCTION; Schema: Kalunga; Owner: -
--

CREATE FUNCTION "Kalunga".check_responsabile_gestore() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF EXISTS (
        SELECT 1
        FROM utente
        WHERE id = NEW.responsabile AND tipo = 'gestore'
    ) THEN
        RETURN NEW;
    ELSE
        RAISE EXCEPTION 'Il responsabile deve essere un utente di tipo gestore';
    END IF;
END;
$$;


--
-- Name: copia_tessera_su_cancellazione(); Type: FUNCTION; Schema: Kalunga; Owner: -
--

CREATE FUNCTION "Kalunga".copia_tessera_su_cancellazione() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    INSERT INTO storico_tessere (tessera_id, saldo_punti, data_rilascio, negozio_id, tipo_evento)
    VALUES (OLD.id, OLD.saldo_punti, OLD.data_rilascio, OLD.negozio, 'cancellazione_tessera');
    RETURN OLD;
END;
$$;


--
-- Name: copia_tessere_su_chiusura_negozio(); Type: FUNCTION; Schema: Kalunga; Owner: -
--

CREATE FUNCTION "Kalunga".copia_tessere_su_chiusura_negozio() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF NEW.eliminato = TRUE AND OLD.eliminato = FALSE THEN
        INSERT INTO storico_tessere (tessera_id, saldo_punti, data_rilascio, negozio_id, tipo_evento)
        SELECT t.id, t.saldo_punti, t.data_rilascio, t.negozio, 'chiusura_negozio'
        FROM tessera t
        WHERE t.negozio = OLD.id;
    END IF;
    RETURN NEW;
END;
$$;


--
-- Name: crea_scontrino_con_prodotti(integer, integer[], integer[], numeric[], integer); Type: FUNCTION; Schema: Kalunga; Owner: -
--

CREATE FUNCTION "Kalunga".crea_scontrino_con_prodotti(p_scontrino_id integer, p_prodotti integer[], p_quantita integer[], p_prezzi numeric[], p_perc_sconto integer) RETURNS void
    LANGUAGE plpgsql
    AS $$
DECLARE
    i INT;
    totale NUMERIC(10,2) := 0;
BEGIN
    RAISE NOTICE 'ID: %, prodotti: %, quantita: %, prezzi: %, sconto: %',
        p_scontrino_id, p_prodotti, p_quantita, p_prezzi, p_perc_sconto;

    FOR i IN 1 .. array_length(p_prodotti, 1) LOOP
        INSERT INTO scontrino_prodotto(scontrino, prodotto, prezzo_unitario, quantita)
        VALUES (p_scontrino_id, p_prodotti[i], p_prezzi[i], p_quantita[i]);

        totale := totale + (p_prezzi[i] * p_quantita[i]);
    END LOOP;

    UPDATE scontrino
    SET totale_pagato = totale
    WHERE id = p_scontrino_id;

    PERFORM applica_sconto_scontrino(p_scontrino_id, p_perc_sconto, totale);
END;
$$;


--
-- Name: prevent_delete_fornitore_if_orders(); Type: FUNCTION; Schema: Kalunga; Owner: -
--

CREATE FUNCTION "Kalunga".prevent_delete_fornitore_if_orders() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
  IF EXISTS (
    SELECT 1
    FROM approvvigionamento
    WHERE approvvigionamento.fornitore = OLD.partita_iva
  ) THEN
    RAISE EXCEPTION 'Impossibile eliminare il fornitore: sono presenti ordini associati';
  END IF;
  RETURN OLD;
END;
$$;


--
-- Name: trigger_effettua_approvv_con_check(); Type: FUNCTION; Schema: Kalunga; Owner: -
--

CREATE FUNCTION "Kalunga".trigger_effettua_approvv_con_check() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    fornitore_id varchar(20);
    prezzo_unitario numeric;
BEGIN
    SELECT f.fornitore, f.costo_unitario
    INTO fornitore_id, prezzo_unitario
    FROM "Kalunga".fornitore_prodotto f
    JOIN "Kalunga".fornitore fo ON f.fornitore = fo.partita_iva
    WHERE f.prodotto = NEW.prodotto
      AND f.disponibilita >= NEW.quantita
      AND fo.eliminato = FALSE
    ORDER BY f.costo_unitario ASC
    LIMIT 1;

    IF fornitore_id IS NULL THEN
        RAISE EXCEPTION 'Nessun fornitore valido trovato per prodotto % con quantità %', NEW.prodotto, NEW.quantita;
    END IF;

    NEW.fornitore := fornitore_id;
    NEW.prezzo_unitario := prezzo_unitario;
    RETURN NEW;
END;
$$;


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: approvvigionamento; Type: TABLE; Schema: Kalunga; Owner: -
--

CREATE TABLE "Kalunga".approvvigionamento (
    id integer NOT NULL,
    negozio integer NOT NULL,
    fornitore character varying(20) NOT NULL,
    data_consegna date NOT NULL,
    prodotto integer NOT NULL,
    prezzo_unitario numeric(10,2) NOT NULL,
    quantita integer NOT NULL,
    CONSTRAINT approvvigionamento_quantita_check CHECK ((quantita > 0))
);


--
-- Name: approvvigionamento_id_seq; Type: SEQUENCE; Schema: Kalunga; Owner: -
--

CREATE SEQUENCE "Kalunga".approvvigionamento_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: approvvigionamento_id_seq; Type: SEQUENCE OWNED BY; Schema: Kalunga; Owner: -
--

ALTER SEQUENCE "Kalunga".approvvigionamento_id_seq OWNED BY "Kalunga".approvvigionamento.id;


--
-- Name: tessera; Type: TABLE; Schema: Kalunga; Owner: -
--

CREATE TABLE "Kalunga".tessera (
    id integer NOT NULL,
    saldo_punti integer,
    data_rilascio date,
    negozio integer,
    utente integer
);


--
-- Name: utente; Type: TABLE; Schema: Kalunga; Owner: -
--

CREATE TABLE "Kalunga".utente (
    id integer NOT NULL,
    nome character varying(50),
    cognome character varying(50),
    email character varying(100),
    password character varying(100),
    tipo character varying(10),
    codice_fiscale character varying(20),
    CONSTRAINT utente_tipo_check CHECK (((tipo)::text = ANY ((ARRAY['gestore'::character varying, 'cliente'::character varying])::text[])))
);


--
-- Name: clienti_con_piu_di_300_punti; Type: VIEW; Schema: Kalunga; Owner: -
--

CREATE VIEW "Kalunga".clienti_con_piu_di_300_punti AS
 SELECT t.id AS tessera_id,
    t.saldo_punti,
    t.data_rilascio,
    t.negozio,
    u.id AS utente_id,
    u.nome,
    u.cognome,
    u.codice_fiscale
   FROM ("Kalunga".tessera t
     JOIN "Kalunga".utente u ON ((t.utente = u.id)))
  WHERE (t.saldo_punti > 300)
  ORDER BY t.saldo_punti DESC;


--
-- Name: fornitore; Type: TABLE; Schema: Kalunga; Owner: -
--

CREATE TABLE "Kalunga".fornitore (
    partita_iva character varying(20) NOT NULL,
    indirizzo text NOT NULL,
    eliminato boolean DEFAULT false
);


--
-- Name: fornitore_prodotto; Type: TABLE; Schema: Kalunga; Owner: -
--

CREATE TABLE "Kalunga".fornitore_prodotto (
    fornitore character varying(20) NOT NULL,
    prodotto integer NOT NULL,
    costo_unitario numeric(10,2) NOT NULL,
    disponibilita integer NOT NULL,
    CONSTRAINT fornitore_prodotto_disponibilita_check CHECK ((disponibilita >= 0))
);


--
-- Name: lista_tesserati_negozio; Type: VIEW; Schema: Kalunga; Owner: -
--

CREATE VIEW "Kalunga".lista_tesserati_negozio AS
 SELECT t.negozio,
    t.id AS tessera_id,
    t.data_rilascio,
    u.id AS utente_id,
    u.nome,
    u.cognome,
    u.codice_fiscale
   FROM ("Kalunga".tessera t
     JOIN "Kalunga".utente u ON ((t.utente = u.id)))
  ORDER BY t.negozio, t.data_rilascio;


--
-- Name: negozio; Type: TABLE; Schema: Kalunga; Owner: -
--

CREATE TABLE "Kalunga".negozio (
    id integer NOT NULL,
    indirizzo text,
    orario_apertura time without time zone,
    orario_chiusura time without time zone,
    responsabile integer NOT NULL,
    eliminato boolean DEFAULT false
);


--
-- Name: negozio_id_seq; Type: SEQUENCE; Schema: Kalunga; Owner: -
--

CREATE SEQUENCE "Kalunga".negozio_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: negozio_id_seq; Type: SEQUENCE OWNED BY; Schema: Kalunga; Owner: -
--

ALTER SEQUENCE "Kalunga".negozio_id_seq OWNED BY "Kalunga".negozio.id;


--
-- Name: prodotto; Type: TABLE; Schema: Kalunga; Owner: -
--

CREATE TABLE "Kalunga".prodotto (
    id integer NOT NULL,
    nome character varying(100) NOT NULL,
    descrizione text
);


--
-- Name: prodotti_offerti_fornitore; Type: VIEW; Schema: Kalunga; Owner: -
--

CREATE VIEW "Kalunga".prodotti_offerti_fornitore AS
 SELECT fp.fornitore AS partita_iva,
    p.id AS id_prodotto,
    p.nome AS nome_prodotto,
    fp.costo_unitario,
    fp.disponibilita
   FROM ("Kalunga".fornitore_prodotto fp
     JOIN "Kalunga".prodotto p ON ((p.id = fp.prodotto)))
  ORDER BY fp.fornitore, p.nome;


--
-- Name: prodotto_id_seq; Type: SEQUENCE; Schema: Kalunga; Owner: -
--

CREATE SEQUENCE "Kalunga".prodotto_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: prodotto_id_seq; Type: SEQUENCE OWNED BY; Schema: Kalunga; Owner: -
--

ALTER SEQUENCE "Kalunga".prodotto_id_seq OWNED BY "Kalunga".prodotto.id;


--
-- Name: prodotto_negozio; Type: TABLE; Schema: Kalunga; Owner: -
--

CREATE TABLE "Kalunga".prodotto_negozio (
    negozio integer NOT NULL,
    prodotto integer NOT NULL,
    prezzo_vendita numeric(10,2) NOT NULL
);


--
-- Name: scontrino; Type: TABLE; Schema: Kalunga; Owner: -
--

CREATE TABLE "Kalunga".scontrino (
    id integer NOT NULL,
    data_acquisto date NOT NULL,
    tessera integer,
    negozio integer NOT NULL,
    sconto_percentuale integer DEFAULT 0,
    totale_pagato numeric(10,2) NOT NULL,
    utente integer NOT NULL
);


--
-- Name: scontrino_id_seq; Type: SEQUENCE; Schema: Kalunga; Owner: -
--

CREATE SEQUENCE "Kalunga".scontrino_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: scontrino_id_seq; Type: SEQUENCE OWNED BY; Schema: Kalunga; Owner: -
--

ALTER SEQUENCE "Kalunga".scontrino_id_seq OWNED BY "Kalunga".scontrino.id;


--
-- Name: scontrino_prodotto; Type: TABLE; Schema: Kalunga; Owner: -
--

CREATE TABLE "Kalunga".scontrino_prodotto (
    scontrino integer NOT NULL,
    prodotto integer NOT NULL,
    prezzo_unitario numeric(10,2) NOT NULL,
    quantita integer NOT NULL,
    CONSTRAINT check_quantita_positive CHECK ((quantita > 0))
);


--
-- Name: storico_tessere; Type: TABLE; Schema: Kalunga; Owner: -
--

CREATE TABLE "Kalunga".storico_tessere (
    id integer NOT NULL,
    tessera_id integer,
    saldo_punti integer,
    data_rilascio date,
    negozio_id integer,
    tipo_evento character varying(30),
    data_evento timestamp without time zone DEFAULT now(),
    CONSTRAINT storico_tessere_tipo_evento_check CHECK (((tipo_evento)::text = ANY ((ARRAY['chiusura_negozio'::character varying, 'cancellazione_tessera'::character varying])::text[])))
);


--
-- Name: storico_tessere_id_seq; Type: SEQUENCE; Schema: Kalunga; Owner: -
--

CREATE SEQUENCE "Kalunga".storico_tessere_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: storico_tessere_id_seq; Type: SEQUENCE OWNED BY; Schema: Kalunga; Owner: -
--

ALTER SEQUENCE "Kalunga".storico_tessere_id_seq OWNED BY "Kalunga".storico_tessere.id;


--
-- Name: tessera_id_seq; Type: SEQUENCE; Schema: Kalunga; Owner: -
--

CREATE SEQUENCE "Kalunga".tessera_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: tessera_id_seq; Type: SEQUENCE OWNED BY; Schema: Kalunga; Owner: -
--

ALTER SEQUENCE "Kalunga".tessera_id_seq OWNED BY "Kalunga".tessera.id;


--
-- Name: utente_id_seq; Type: SEQUENCE; Schema: Kalunga; Owner: -
--

CREATE SEQUENCE "Kalunga".utente_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: utente_id_seq; Type: SEQUENCE OWNED BY; Schema: Kalunga; Owner: -
--

ALTER SEQUENCE "Kalunga".utente_id_seq OWNED BY "Kalunga".utente.id;


--
-- Name: approvvigionamento id; Type: DEFAULT; Schema: Kalunga; Owner: -
--

ALTER TABLE ONLY "Kalunga".approvvigionamento ALTER COLUMN id SET DEFAULT nextval('"Kalunga".approvvigionamento_id_seq'::regclass);


--
-- Name: negozio id; Type: DEFAULT; Schema: Kalunga; Owner: -
--

ALTER TABLE ONLY "Kalunga".negozio ALTER COLUMN id SET DEFAULT nextval('"Kalunga".negozio_id_seq'::regclass);


--
-- Name: prodotto id; Type: DEFAULT; Schema: Kalunga; Owner: -
--

ALTER TABLE ONLY "Kalunga".prodotto ALTER COLUMN id SET DEFAULT nextval('"Kalunga".prodotto_id_seq'::regclass);


--
-- Name: scontrino id; Type: DEFAULT; Schema: Kalunga; Owner: -
--

ALTER TABLE ONLY "Kalunga".scontrino ALTER COLUMN id SET DEFAULT nextval('"Kalunga".scontrino_id_seq'::regclass);


--
-- Name: storico_tessere id; Type: DEFAULT; Schema: Kalunga; Owner: -
--

ALTER TABLE ONLY "Kalunga".storico_tessere ALTER COLUMN id SET DEFAULT nextval('"Kalunga".storico_tessere_id_seq'::regclass);


--
-- Name: tessera id; Type: DEFAULT; Schema: Kalunga; Owner: -
--

ALTER TABLE ONLY "Kalunga".tessera ALTER COLUMN id SET DEFAULT nextval('"Kalunga".tessera_id_seq'::regclass);


--
-- Name: utente id; Type: DEFAULT; Schema: Kalunga; Owner: -
--

ALTER TABLE ONLY "Kalunga".utente ALTER COLUMN id SET DEFAULT nextval('"Kalunga".utente_id_seq'::regclass);


--
-- Data for Name: approvvigionamento; Type: TABLE DATA; Schema: Kalunga; Owner: -
--

INSERT INTO "Kalunga".approvvigionamento VALUES (3, 11, 'IT88899900011', '2025-07-10', 23, 1.00, 10);
INSERT INTO "Kalunga".approvvigionamento VALUES (9, 4, 'IT88899900011', '2025-07-10', 5, 0.32, 10);
INSERT INTO "Kalunga".approvvigionamento VALUES (10, 16, 'IT11122233344', '2025-07-10', 3, 1.00, 5);
INSERT INTO "Kalunga".approvvigionamento VALUES (11, 16, 'IT11122233344', '2025-07-10', 6, 1.75, 10);
INSERT INTO "Kalunga".approvvigionamento VALUES (12, 16, 'IT12345678901', '2025-07-10', 19, 1.85, 15);
INSERT INTO "Kalunga".approvvigionamento VALUES (13, 16, 'IT11122233344', '2025-07-10', 3, 1.00, 5);
INSERT INTO "Kalunga".approvvigionamento VALUES (14, 16, 'IT11122233344', '2025-07-10', 6, 1.75, 10);
INSERT INTO "Kalunga".approvvigionamento VALUES (15, 16, 'IT12345678901', '2025-07-10', 19, 1.85, 15);
INSERT INTO "Kalunga".approvvigionamento VALUES (18, 16, 'IT11122233344', '2025-07-10', 16, 0.50, 22);
INSERT INTO "Kalunga".approvvigionamento VALUES (19, 16, 'IT12345678901', '2025-07-10', 2, 5.00, 55);
INSERT INTO "Kalunga".approvvigionamento VALUES (20, 16, 'IT44455566677', '2025-07-10', 13, 0.64, 13);
INSERT INTO "Kalunga".approvvigionamento VALUES (22, 16, 'IT11122233344', '2025-07-10', 16, 0.50, 22);
INSERT INTO "Kalunga".approvvigionamento VALUES (23, 16, 'IT98765432109', '2025-07-10', 2, 5.10, 55);
INSERT INTO "Kalunga".approvvigionamento VALUES (24, 16, 'IT44455566677', '2025-07-10', 13, 0.64, 13);
INSERT INTO "Kalunga".approvvigionamento VALUES (26, 16, 'IT11122233344', '2025-07-10', 16, 0.50, 22);
INSERT INTO "Kalunga".approvvigionamento VALUES (28, 16, 'IT44455566677', '2025-07-10', 13, 0.64, 13);
INSERT INTO "Kalunga".approvvigionamento VALUES (30, 16, 'IT11122233344', '2025-07-10', 16, 0.50, 22);
INSERT INTO "Kalunga".approvvigionamento VALUES (32, 16, 'IT44455566677', '2025-07-10', 13, 0.64, 13);
INSERT INTO "Kalunga".approvvigionamento VALUES (34, 16, 'IT12345678901', '2025-07-10', 16, 0.92, 22);
INSERT INTO "Kalunga".approvvigionamento VALUES (36, 16, 'IT44455566677', '2025-07-10', 13, 0.64, 13);
INSERT INTO "Kalunga".approvvigionamento VALUES (38, 16, 'IT12345678901', '2025-07-10', 16, 0.92, 22);
INSERT INTO "Kalunga".approvvigionamento VALUES (40, 16, 'IT44455566677', '2025-07-10', 13, 0.64, 13);
INSERT INTO "Kalunga".approvvigionamento VALUES (42, 16, 'IT12345678901', '2025-07-10', 16, 0.92, 22);
INSERT INTO "Kalunga".approvvigionamento VALUES (44, 16, 'IT44455566677', '2025-07-10', 13, 0.64, 13);
INSERT INTO "Kalunga".approvvigionamento VALUES (46, 16, 'IT12345678901', '2025-07-10', 16, 0.92, 22);
INSERT INTO "Kalunga".approvvigionamento VALUES (48, 16, 'IT44455566677', '2025-07-10', 13, 0.64, 13);
INSERT INTO "Kalunga".approvvigionamento VALUES (50, 16, 'IT11122233344', '2025-07-10', 2, 1.50, 3);
INSERT INTO "Kalunga".approvvigionamento VALUES (52, 16, 'IT12345678901', '2025-07-10', 20, 1.15, 1);
INSERT INTO "Kalunga".approvvigionamento VALUES (53, 16, 'IT12345678901', '2025-07-10', 20, 1.15, 1);
INSERT INTO "Kalunga".approvvigionamento VALUES (54, 16, 'IT12345678901', '2025-07-18', 20, 1.15, 2);


--
-- Data for Name: fornitore; Type: TABLE DATA; Schema: Kalunga; Owner: -
--

INSERT INTO "Kalunga".fornitore VALUES ('IT12345678901', 'Via Milano 1, Milano', false);
INSERT INTO "Kalunga".fornitore VALUES ('IT98765432109', 'Via Roma 22, Torino', false);
INSERT INTO "Kalunga".fornitore VALUES ('IT22233344455', 'Via Manzoni 10, Milano', false);
INSERT INTO "Kalunga".fornitore VALUES ('IT55566677788', 'Corso Francia 18, Torino', false);
INSERT INTO "Kalunga".fornitore VALUES ('IT88899900011', 'Piazza Repubblica 25, Firenze', false);
INSERT INTO "Kalunga".fornitore VALUES ('IT11122233344', 'Via Toledo 5, Napoli', false);
INSERT INTO "Kalunga".fornitore VALUES ('IT44455566677', 'Viale Regione Siciliana 120, Palermo', false);


--
-- Data for Name: fornitore_prodotto; Type: TABLE DATA; Schema: Kalunga; Owner: -
--

INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT12345678901', 2, 5.00, 25);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT98765432109', 2, 5.10, 15);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT11122233344', 16, 0.50, 12);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT12345678901', 16, 0.92, 12);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT44455566677', 13, 0.64, 46);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT11122233344', 2, 1.50, 47);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT12345678901', 20, 1.15, 136);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT12345678901', 1, 4.50, 100);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT12345678901', 3, 1.20, 200);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT12345678901', 4, 0.30, 300);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT12345678901', 5, 0.35, 300);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT98765432109', 1, 4.40, 90);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT98765432109', 6, 1.80, 50);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT98765432109', 7, 2.00, 100);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT98765432109', 8, 0.90, 150);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT22233344455', 9, 0.60, 80);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT22233344455', 10, 0.75, 120);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT22233344455', 11, 1.10, 140);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT22233344455', 12, 0.55, 200);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT22233344455', 13, 0.65, 210);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT55566677788', 14, 2.50, 60);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT55566677788', 15, 3.20, 50);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT55566677788', 16, 0.95, 90);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT55566677788', 17, 0.70, 110);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT55566677788', 18, 1.00, 130);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT88899900011', 19, 1.90, 75);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT88899900011', 20, 1.20, 85);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT88899900011', 3, 1.25, 100);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT11122233344', 7, 1.95, 70);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT11122233344', 8, 0.88, 95);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT11122233344', 9, 0.62, 110);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT11122233344', 10, 0.72, 120);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT44455566677', 11, 1.08, 130);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT44455566677', 12, 0.52, 140);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT44455566677', 14, 2.45, 80);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT44455566677', 15, 3.15, 90);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT12345678901', 17, 0.68, 110);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT98765432109', 4, 0.33, 100);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT98765432109', 5, 0.38, 110);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT55566677788', 6, 1.82, 50);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT55566677788', 7, 2.02, 60);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT55566677788', 8, 0.93, 70);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT55566677788', 9, 0.67, 80);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT55566677788', 10, 0.78, 90);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT98765432109', 3, 1.18, 75);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT88899900011', 4, 0.28, 185);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT12345678901', 18, 0.98, 99);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT55566677788', 23, 1.10, 500);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT88899900011', 23, 1.00, 90);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT11122233344', 17, 0.80, 200);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT88899900011', 5, 0.32, 180);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT11122233344', 3, 1.00, 190);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT11122233344', 6, 1.75, 40);
INSERT INTO "Kalunga".fornitore_prodotto VALUES ('IT12345678901', 19, 1.85, 100);


--
-- Data for Name: negozio; Type: TABLE DATA; Schema: Kalunga; Owner: -
--

INSERT INTO "Kalunga".negozio VALUES (4, 'Corso Buenos Aires 10, Milano', '09:00:00', '20:00:00', 1, false);
INSERT INTO "Kalunga".negozio VALUES (5, 'Via Garibaldi 5, Torino', '10:00:00', '21:00:00', 4, false);
INSERT INTO "Kalunga".negozio VALUES (7, 'Viale Liberta, Monza', '12:00:00', '22:00:00', 1, false);
INSERT INTO "Kalunga".negozio VALUES (11, 'Corso Italia 45, Torino', '10:00:00', '21:00:00', 5, false);
INSERT INTO "Kalunga".negozio VALUES (13, 'Via Roma 33, Napoli', '09:30:00', '20:30:00', 5, false);
INSERT INTO "Kalunga".negozio VALUES (14, 'Via Libertà 55, Palermo', '10:00:00', '21:00:00', 6, false);
INSERT INTO "Kalunga".negozio VALUES (12, 'Piazza Duomo 8, Firenze', '11:00:00', '22:00:00', 6, true);
INSERT INTO "Kalunga".negozio VALUES (10, 'Via Vittorio Emanuele 12, Milano', '09:00:00', '20:00:00', 1, true);
INSERT INTO "Kalunga".negozio VALUES (15, 'Via Conquista 12, Brasil', '06:00:00', '13:00:00', 6, true);
INSERT INTO "Kalunga".negozio VALUES (16, 'via vitoria conquista 2', '08:00:00', '22:00:00', 6, false);


--
-- Data for Name: prodotto; Type: TABLE DATA; Schema: Kalunga; Owner: -
--

INSERT INTO "Kalunga".prodotto VALUES (1, 'Pizza Margherita', 'Pizza con pomodoro e mozzarella');
INSERT INTO "Kalunga".prodotto VALUES (2, 'Birra', 'Birra artigianale 33cl');
INSERT INTO "Kalunga".prodotto VALUES (3, 'Acqua', 'Bottiglia di acqua naturale 50cl');
INSERT INTO "Kalunga".prodotto VALUES (5, 'Pizza Diavola', 'Pizza con salame piccante e mozzarella');
INSERT INTO "Kalunga".prodotto VALUES (6, 'Birra Artigianale', 'Birra rossa artigianale 33cl');
INSERT INTO "Kalunga".prodotto VALUES (7, 'Acqua Naturale', 'Bottiglia di acqua naturale 50cl');
INSERT INTO "Kalunga".prodotto VALUES (8, 'Acqua Frizzante', 'Bottiglia di acqua frizzante 50cl');
INSERT INTO "Kalunga".prodotto VALUES (9, 'Pane Integrale', 'Pane artigianale integrale da 500g');
INSERT INTO "Kalunga".prodotto VALUES (10, 'Focaccia', 'Focaccia ligure con olio extravergine');
INSERT INTO "Kalunga".prodotto VALUES (11, 'Caffè Espresso', 'Miscela arabica torrefatta');
INSERT INTO "Kalunga".prodotto VALUES (12, 'Cornetto Vuoto', 'Cornetto sfogliato');
INSERT INTO "Kalunga".prodotto VALUES (13, 'Cornetto alla Crema', 'Cornetto ripieno di crema pasticcera');
INSERT INTO "Kalunga".prodotto VALUES (14, 'Succhi di Frutta', 'Succo alla pesca 200ml');
INSERT INTO "Kalunga".prodotto VALUES (15, 'Mela', 'Mela rossa del Trentino');
INSERT INTO "Kalunga".prodotto VALUES (16, 'Banana', 'Banana equador biologica');
INSERT INTO "Kalunga".prodotto VALUES (17, 'Mozzarella di Bufala', 'Mozzarella DOP campana 250g');
INSERT INTO "Kalunga".prodotto VALUES (18, 'Prosciutto Crudo', 'Prosciutto stagionato 18 mesi');
INSERT INTO "Kalunga".prodotto VALUES (19, 'Insalata Mista', NULL);
INSERT INTO "Kalunga".prodotto VALUES (20, 'Yogurt Bianco', 'Yogurt naturale intero 125g');
INSERT INTO "Kalunga".prodotto VALUES (21, 'Tè Freddo Limone', 'Bottiglia di tè freddo al limone 500ml');
INSERT INTO "Kalunga".prodotto VALUES (22, 'Cioccolato Fondente', NULL);
INSERT INTO "Kalunga".prodotto VALUES (23, 'Patatine Chips', 'Confezione patatine croccanti 50g');
INSERT INTO "Kalunga".prodotto VALUES (4, 'Pizza 4 Formaggi', 'Pizza con formaggi');


--
-- Data for Name: prodotto_negozio; Type: TABLE DATA; Schema: Kalunga; Owner: -
--

INSERT INTO "Kalunga".prodotto_negozio VALUES (4, 1, 6.50);
INSERT INTO "Kalunga".prodotto_negozio VALUES (5, 1, 6.20);
INSERT INTO "Kalunga".prodotto_negozio VALUES (7, 1, 6.80);
INSERT INTO "Kalunga".prodotto_negozio VALUES (11, 1, 6.40);
INSERT INTO "Kalunga".prodotto_negozio VALUES (13, 1, 6.55);
INSERT INTO "Kalunga".prodotto_negozio VALUES (4, 3, 2.10);
INSERT INTO "Kalunga".prodotto_negozio VALUES (5, 3, 2.00);
INSERT INTO "Kalunga".prodotto_negozio VALUES (7, 3, 2.25);
INSERT INTO "Kalunga".prodotto_negozio VALUES (11, 3, 2.05);
INSERT INTO "Kalunga".prodotto_negozio VALUES (13, 3, 2.15);
INSERT INTO "Kalunga".prodotto_negozio VALUES (4, 5, 0.60);
INSERT INTO "Kalunga".prodotto_negozio VALUES (5, 5, 0.58);
INSERT INTO "Kalunga".prodotto_negozio VALUES (7, 5, 0.62);
INSERT INTO "Kalunga".prodotto_negozio VALUES (11, 5, 0.59);
INSERT INTO "Kalunga".prodotto_negozio VALUES (13, 5, 0.61);
INSERT INTO "Kalunga".prodotto_negozio VALUES (4, 10, 1.00);
INSERT INTO "Kalunga".prodotto_negozio VALUES (5, 10, 1.05);
INSERT INTO "Kalunga".prodotto_negozio VALUES (7, 10, 0.98);
INSERT INTO "Kalunga".prodotto_negozio VALUES (11, 10, 1.10);
INSERT INTO "Kalunga".prodotto_negozio VALUES (13, 10, 1.00);
INSERT INTO "Kalunga".prodotto_negozio VALUES (14, 1, 6.45);
INSERT INTO "Kalunga".prodotto_negozio VALUES (12, 1, 6.35);
INSERT INTO "Kalunga".prodotto_negozio VALUES (10, 1, 6.50);
INSERT INTO "Kalunga".prodotto_negozio VALUES (14, 3, 2.18);
INSERT INTO "Kalunga".prodotto_negozio VALUES (12, 3, 2.12);
INSERT INTO "Kalunga".prodotto_negozio VALUES (10, 3, 2.22);
INSERT INTO "Kalunga".prodotto_negozio VALUES (14, 5, 0.63);
INSERT INTO "Kalunga".prodotto_negozio VALUES (12, 5, 0.60);
INSERT INTO "Kalunga".prodotto_negozio VALUES (10, 5, 0.65);
INSERT INTO "Kalunga".prodotto_negozio VALUES (14, 10, 1.02);
INSERT INTO "Kalunga".prodotto_negozio VALUES (12, 10, 1.00);
INSERT INTO "Kalunga".prodotto_negozio VALUES (10, 10, 1.05);
INSERT INTO "Kalunga".prodotto_negozio VALUES (4, 11, 2.71);
INSERT INTO "Kalunga".prodotto_negozio VALUES (4, 9, 3.39);
INSERT INTO "Kalunga".prodotto_negozio VALUES (4, 15, 4.35);
INSERT INTO "Kalunga".prodotto_negozio VALUES (4, 19, 5.90);
INSERT INTO "Kalunga".prodotto_negozio VALUES (4, 17, 1.01);
INSERT INTO "Kalunga".prodotto_negozio VALUES (4, 4, 1.92);
INSERT INTO "Kalunga".prodotto_negozio VALUES (4, 6, 3.03);
INSERT INTO "Kalunga".prodotto_negozio VALUES (4, 14, 3.18);
INSERT INTO "Kalunga".prodotto_negozio VALUES (4, 13, 2.10);
INSERT INTO "Kalunga".prodotto_negozio VALUES (4, 2, 1.46);
INSERT INTO "Kalunga".prodotto_negozio VALUES (4, 16, 1.96);
INSERT INTO "Kalunga".prodotto_negozio VALUES (4, 7, 2.57);
INSERT INTO "Kalunga".prodotto_negozio VALUES (4, 12, 4.73);
INSERT INTO "Kalunga".prodotto_negozio VALUES (4, 20, 5.02);
INSERT INTO "Kalunga".prodotto_negozio VALUES (4, 18, 2.16);
INSERT INTO "Kalunga".prodotto_negozio VALUES (4, 8, 3.88);
INSERT INTO "Kalunga".prodotto_negozio VALUES (16, 6, 2.00);
INSERT INTO "Kalunga".prodotto_negozio VALUES (16, 3, 1.50);


--
-- Data for Name: scontrino; Type: TABLE DATA; Schema: Kalunga; Owner: -
--

INSERT INTO "Kalunga".scontrino VALUES (1, '2025-07-05', 15, 13, 5, 25.46, 7);
INSERT INTO "Kalunga".scontrino VALUES (2, '2025-07-08', 5, 14, 0, 34.70, 2);
INSERT INTO "Kalunga".scontrino VALUES (3, '2025-07-08', 5, 4, 0, 473.00, 2);
INSERT INTO "Kalunga".scontrino VALUES (4, '2025-07-08', 5, 7, 0, 680.00, 2);
INSERT INTO "Kalunga".scontrino VALUES (5, '2025-07-08', 5, 7, 0, 539.00, 2);
INSERT INTO "Kalunga".scontrino VALUES (6, '2025-07-08', 5, 11, 0, 4972.80, 2);
INSERT INTO "Kalunga".scontrino VALUES (7, '2025-07-08', 5, 4, 0, 65.00, 2);
INSERT INTO "Kalunga".scontrino VALUES (8, '2025-07-08', 5, 4, 0, 14.60, 2);
INSERT INTO "Kalunga".scontrino VALUES (9, '2025-07-08', 5, 14, 0, 6.30, 2);
INSERT INTO "Kalunga".scontrino VALUES (10, '2025-07-08', 5, 4, 0, 32.50, 2);
INSERT INTO "Kalunga".scontrino VALUES (11, '2025-07-08', 5, 4, 0, 32.50, 2);
INSERT INTO "Kalunga".scontrino VALUES (12, '2025-07-10', 14, 4, 0, 13.00, 6);
INSERT INTO "Kalunga".scontrino VALUES (13, '2025-07-10', 14, 4, 0, 0.00, 6);
INSERT INTO "Kalunga".scontrino VALUES (14, '2025-07-10', 14, 5, 0, 22.00, 6);
INSERT INTO "Kalunga".scontrino VALUES (15, '2025-07-10', 14, 7, 0, 22.00, 6);
INSERT INTO "Kalunga".scontrino VALUES (16, '2025-07-10', 14, 11, 0, 0.00, 6);
INSERT INTO "Kalunga".scontrino VALUES (17, '2025-07-10', 14, 13, 0, 0.00, 6);
INSERT INTO "Kalunga".scontrino VALUES (18, '2025-07-10', 14, 13, 0, 0.00, 6);
INSERT INTO "Kalunga".scontrino VALUES (19, '2025-07-10', 14, 14, 0, 0.00, 6);
INSERT INTO "Kalunga".scontrino VALUES (20, '2025-07-10', 14, 14, 0, 2.04, 6);
INSERT INTO "Kalunga".scontrino VALUES (21, '2025-07-10', 14, 4, 0, 13.00, 6);
INSERT INTO "Kalunga".scontrino VALUES (22, '2025-07-10', 14, 5, 5, 1.90, 6);


--
-- Data for Name: scontrino_prodotto; Type: TABLE DATA; Schema: Kalunga; Owner: -
--

INSERT INTO "Kalunga".scontrino_prodotto VALUES (1, 1, 5.00, 4);
INSERT INTO "Kalunga".scontrino_prodotto VALUES (1, 2, 1.70, 4);
INSERT INTO "Kalunga".scontrino_prodotto VALUES (2, 1, 6.45, 2);
INSERT INTO "Kalunga".scontrino_prodotto VALUES (2, 3, 2.18, 10);
INSERT INTO "Kalunga".scontrino_prodotto VALUES (3, 12, 4.73, 100);
INSERT INTO "Kalunga".scontrino_prodotto VALUES (4, 1, 6.80, 100);
INSERT INTO "Kalunga".scontrino_prodotto VALUES (5, 10, 0.98, 550);
INSERT INTO "Kalunga".scontrino_prodotto VALUES (6, 1, 6.40, 777);
INSERT INTO "Kalunga".scontrino_prodotto VALUES (7, 1, 6.50, 10);
INSERT INTO "Kalunga".scontrino_prodotto VALUES (8, 2, 1.46, 10);
INSERT INTO "Kalunga".scontrino_prodotto VALUES (9, 5, 0.63, 10);
INSERT INTO "Kalunga".scontrino_prodotto VALUES (10, 1, 6.50, 5);
INSERT INTO "Kalunga".scontrino_prodotto VALUES (11, 1, 6.50, 5);
INSERT INTO "Kalunga".scontrino_prodotto VALUES (12, 1, 6.50, 2);
INSERT INTO "Kalunga".scontrino_prodotto VALUES (14, 1, 6.50, 2);
INSERT INTO "Kalunga".scontrino_prodotto VALUES (14, 2, 3.00, 3);
INSERT INTO "Kalunga".scontrino_prodotto VALUES (15, 1, 6.50, 2);
INSERT INTO "Kalunga".scontrino_prodotto VALUES (15, 2, 3.00, 3);
INSERT INTO "Kalunga".scontrino_prodotto VALUES (20, 10, 1.02, 2);
INSERT INTO "Kalunga".scontrino_prodotto VALUES (21, 1, 6.50, 2);
INSERT INTO "Kalunga".scontrino_prodotto VALUES (22, 3, 2.00, 1);


--
-- Data for Name: storico_tessere; Type: TABLE DATA; Schema: Kalunga; Owner: -
--

INSERT INTO "Kalunga".storico_tessere VALUES (1, 16, 250, '2025-07-04', 10, 'cancellazione_tessera', '2025-07-05 12:25:17.459258');
INSERT INTO "Kalunga".storico_tessere VALUES (2, 19, 250, '2025-07-05', 10, 'chiusura_negozio', '2025-07-05 12:26:20.793522');


--
-- Data for Name: tessera; Type: TABLE DATA; Schema: Kalunga; Owner: -
--

INSERT INTO "Kalunga".tessera VALUES (6, 0, '2025-06-10', 5, 3);
INSERT INTO "Kalunga".tessera VALUES (13, 50, '2025-07-01', 4, 5);
INSERT INTO "Kalunga".tessera VALUES (17, 310, '2025-07-05', 13, 9);
INSERT INTO "Kalunga".tessera VALUES (19, 250, '2025-07-05', 10, 8);
INSERT INTO "Kalunga".tessera VALUES (15, 20, '2025-07-03', 7, 7);
INSERT INTO "Kalunga".tessera VALUES (5, 667, '2025-06-01', 4, 2);
INSERT INTO "Kalunga".tessera VALUES (14, 16, '2025-07-02', 5, 6);


--
-- Data for Name: utente; Type: TABLE DATA; Schema: Kalunga; Owner: -
--

INSERT INTO "Kalunga".utente VALUES (1, 'Mario', 'Rossi', 'mario.rossi@mail.com', 'password123', 'gestore', 'MRRSSI80A01F205X');
INSERT INTO "Kalunga".utente VALUES (4, 'Laura', 'Neri', 'laura.neri@mail.com', 'password123', 'gestore', 'LRNRI95D01F205X');
INSERT INTO "Kalunga".utente VALUES (5, 'Andrea', 'Gallo', 'andrea.gallo@mail.com', 'password123', 'gestore', 'NDGLL90C01F205X');
INSERT INTO "Kalunga".utente VALUES (6, 'Sara', 'Ferri', 'sara.ferri@mail.com', 'password123', 'gestore', 'SRFRR95D01F205X');
INSERT INTO "Kalunga".utente VALUES (7, 'Paolo', 'Conti', 'paolo.conti@mail.com', 'password123', 'cliente', 'PLCNTI91E01F205X');
INSERT INTO "Kalunga".utente VALUES (8, 'Elisa', 'Moretti', 'elisa.moretti@mail.com', 'password123', 'cliente', 'LSMRTT92F01F205X');
INSERT INTO "Kalunga".utente VALUES (9, 'Fabio', 'Ricci', 'fabio.ricci@mail.com', 'password123', 'cliente', 'FBRCCI93G01F205X');
INSERT INTO "Kalunga".utente VALUES (10, 'Giulia', 'Fontana', 'giulia.fontana@mail.com', 'password123', 'cliente', 'GLFNTN94H01F205X');
INSERT INTO "Kalunga".utente VALUES (11, 'Stefano', 'Testa', 'stefano.testa@mail.com', 'password123', 'cliente', 'SFTSTT95I01F205X');
INSERT INTO "Kalunga".utente VALUES (12, 'Marta', 'Vitali', 'marta.vitali@mail.com', 'password123', 'cliente', 'MRTVTL96L01F205X');
INSERT INTO "Kalunga".utente VALUES (13, 'Giorgio', 'Russo', 'giorgio.russo@mail.com', 'password123', 'cliente', 'GRGRSS97M01F205X');
INSERT INTO "Kalunga".utente VALUES (14, 'Alessia', 'Marino', 'alessia.marino@mail.com', 'password123', 'cliente', 'LSMRNN98N01F205X');
INSERT INTO "Kalunga".utente VALUES (15, 'Davide', 'Greco', 'davide.greco@mail.com', 'password123', 'cliente', 'DVDGRC99P01F205X');
INSERT INTO "Kalunga".utente VALUES (2, 'Luca', 'Bianchi', 'luca.bianchi@mail.com', 'password1234', 'cliente', 'LCBNCH85B01F205X');
INSERT INTO "Kalunga".utente VALUES (3, 'Anna', 'Verdi', 'anna.verdi@mail.com', 'asdasd123', 'cliente', 'ANVRDI90C01F205X');


--
-- Name: approvvigionamento_id_seq; Type: SEQUENCE SET; Schema: Kalunga; Owner: -
--

SELECT pg_catalog.setval('"Kalunga".approvvigionamento_id_seq', 54, true);


--
-- Name: negozio_id_seq; Type: SEQUENCE SET; Schema: Kalunga; Owner: -
--

SELECT pg_catalog.setval('"Kalunga".negozio_id_seq', 16, true);


--
-- Name: prodotto_id_seq; Type: SEQUENCE SET; Schema: Kalunga; Owner: -
--

SELECT pg_catalog.setval('"Kalunga".prodotto_id_seq', 23, true);


--
-- Name: scontrino_id_seq; Type: SEQUENCE SET; Schema: Kalunga; Owner: -
--

SELECT pg_catalog.setval('"Kalunga".scontrino_id_seq', 23, true);


--
-- Name: storico_tessere_id_seq; Type: SEQUENCE SET; Schema: Kalunga; Owner: -
--

SELECT pg_catalog.setval('"Kalunga".storico_tessere_id_seq', 2, true);


--
-- Name: tessera_id_seq; Type: SEQUENCE SET; Schema: Kalunga; Owner: -
--

SELECT pg_catalog.setval('"Kalunga".tessera_id_seq', 19, true);


--
-- Name: utente_id_seq; Type: SEQUENCE SET; Schema: Kalunga; Owner: -
--

SELECT pg_catalog.setval('"Kalunga".utente_id_seq', 15, true);


--
-- Name: approvvigionamento approvvigionamento_pkey; Type: CONSTRAINT; Schema: Kalunga; Owner: -
--

ALTER TABLE ONLY "Kalunga".approvvigionamento
    ADD CONSTRAINT approvvigionamento_pkey PRIMARY KEY (id);


--
-- Name: fornitore fornitore_pkey; Type: CONSTRAINT; Schema: Kalunga; Owner: -
--

ALTER TABLE ONLY "Kalunga".fornitore
    ADD CONSTRAINT fornitore_pkey PRIMARY KEY (partita_iva);


--
-- Name: fornitore_prodotto fornitore_prodotto_pkey; Type: CONSTRAINT; Schema: Kalunga; Owner: -
--

ALTER TABLE ONLY "Kalunga".fornitore_prodotto
    ADD CONSTRAINT fornitore_prodotto_pkey PRIMARY KEY (fornitore, prodotto);


--
-- Name: negozio negozio_pkey; Type: CONSTRAINT; Schema: Kalunga; Owner: -
--

ALTER TABLE ONLY "Kalunga".negozio
    ADD CONSTRAINT negozio_pkey PRIMARY KEY (id);


--
-- Name: prodotto_negozio prodotto_negozio_pkey; Type: CONSTRAINT; Schema: Kalunga; Owner: -
--

ALTER TABLE ONLY "Kalunga".prodotto_negozio
    ADD CONSTRAINT prodotto_negozio_pkey PRIMARY KEY (negozio, prodotto);


--
-- Name: prodotto prodotto_pkey; Type: CONSTRAINT; Schema: Kalunga; Owner: -
--

ALTER TABLE ONLY "Kalunga".prodotto
    ADD CONSTRAINT prodotto_pkey PRIMARY KEY (id);


--
-- Name: scontrino scontrino_pkey; Type: CONSTRAINT; Schema: Kalunga; Owner: -
--

ALTER TABLE ONLY "Kalunga".scontrino
    ADD CONSTRAINT scontrino_pkey PRIMARY KEY (id);


--
-- Name: scontrino_prodotto scontrino_prodotto_pkey; Type: CONSTRAINT; Schema: Kalunga; Owner: -
--

ALTER TABLE ONLY "Kalunga".scontrino_prodotto
    ADD CONSTRAINT scontrino_prodotto_pkey PRIMARY KEY (scontrino, prodotto);


--
-- Name: storico_tessere storico_tessere_pkey; Type: CONSTRAINT; Schema: Kalunga; Owner: -
--

ALTER TABLE ONLY "Kalunga".storico_tessere
    ADD CONSTRAINT storico_tessere_pkey PRIMARY KEY (id);


--
-- Name: tessera tessera_pkey; Type: CONSTRAINT; Schema: Kalunga; Owner: -
--

ALTER TABLE ONLY "Kalunga".tessera
    ADD CONSTRAINT tessera_pkey PRIMARY KEY (id);


--
-- Name: utente unique_codice_fiscale; Type: CONSTRAINT; Schema: Kalunga; Owner: -
--

ALTER TABLE ONLY "Kalunga".utente
    ADD CONSTRAINT unique_codice_fiscale UNIQUE (codice_fiscale);


--
-- Name: tessera unique_utente_tessera; Type: CONSTRAINT; Schema: Kalunga; Owner: -
--

ALTER TABLE ONLY "Kalunga".tessera
    ADD CONSTRAINT unique_utente_tessera UNIQUE (utente);


--
-- Name: utente utente_pkey; Type: CONSTRAINT; Schema: Kalunga; Owner: -
--

ALTER TABLE ONLY "Kalunga".utente
    ADD CONSTRAINT utente_pkey PRIMARY KEY (id);


--
-- Name: approvvigionamento after_insert_approvvigionamento; Type: TRIGGER; Schema: Kalunga; Owner: -
--

CREATE TRIGGER after_insert_approvvigionamento AFTER INSERT ON "Kalunga".approvvigionamento FOR EACH ROW EXECUTE FUNCTION "Kalunga".aggiorna_disponibilita_fornitore();


--
-- Name: approvvigionamento before_insert_approvv_con_check; Type: TRIGGER; Schema: Kalunga; Owner: -
--

CREATE TRIGGER before_insert_approvv_con_check BEFORE INSERT ON "Kalunga".approvvigionamento FOR EACH ROW EXECUTE FUNCTION "Kalunga".trigger_effettua_approvv_con_check();


--
-- Name: fornitore trg_prevent_delete_fornitore; Type: TRIGGER; Schema: Kalunga; Owner: -
--

CREATE TRIGGER trg_prevent_delete_fornitore BEFORE DELETE ON "Kalunga".fornitore FOR EACH ROW EXECUTE FUNCTION "Kalunga".prevent_delete_fornitore_if_orders();


--
-- Name: scontrino trigger_check_coerenza_scontrino; Type: TRIGGER; Schema: Kalunga; Owner: -
--

CREATE TRIGGER trigger_check_coerenza_scontrino BEFORE INSERT OR UPDATE ON "Kalunga".scontrino FOR EACH ROW EXECUTE FUNCTION "Kalunga".check_coerenza_scontrino();


--
-- Name: scontrino trigger_check_negozio_scontrino; Type: TRIGGER; Schema: Kalunga; Owner: -
--

CREATE TRIGGER trigger_check_negozio_scontrino BEFORE INSERT ON "Kalunga".scontrino FOR EACH ROW EXECUTE FUNCTION "Kalunga".check_negozio_non_eliminato_scontrino();


--
-- Name: tessera trigger_check_negozio_tessera; Type: TRIGGER; Schema: Kalunga; Owner: -
--

CREATE TRIGGER trigger_check_negozio_tessera BEFORE INSERT ON "Kalunga".tessera FOR EACH ROW EXECUTE FUNCTION "Kalunga".check_negozio_non_eliminato_tessera();


--
-- Name: negozio trigger_check_responsabile; Type: TRIGGER; Schema: Kalunga; Owner: -
--

CREATE TRIGGER trigger_check_responsabile BEFORE INSERT OR UPDATE ON "Kalunga".negozio FOR EACH ROW EXECUTE FUNCTION "Kalunga".check_responsabile_gestore();


--
-- Name: utente trigger_check_tipo_utente; Type: TRIGGER; Schema: Kalunga; Owner: -
--

CREATE TRIGGER trigger_check_tipo_utente BEFORE UPDATE ON "Kalunga".utente FOR EACH ROW EXECUTE FUNCTION "Kalunga".check_modifica_tipo_utente();


--
-- Name: tessera trigger_copia_tessera_su_delete; Type: TRIGGER; Schema: Kalunga; Owner: -
--

CREATE TRIGGER trigger_copia_tessera_su_delete BEFORE DELETE ON "Kalunga".tessera FOR EACH ROW EXECUTE FUNCTION "Kalunga".copia_tessera_su_cancellazione();


--
-- Name: negozio trigger_copia_tessere_su_chiusura; Type: TRIGGER; Schema: Kalunga; Owner: -
--

CREATE TRIGGER trigger_copia_tessere_su_chiusura AFTER UPDATE ON "Kalunga".negozio FOR EACH ROW EXECUTE FUNCTION "Kalunga".copia_tessere_su_chiusura_negozio();


--
-- Name: approvvigionamento approvvigionamento_fornitore_fkey; Type: FK CONSTRAINT; Schema: Kalunga; Owner: -
--

ALTER TABLE ONLY "Kalunga".approvvigionamento
    ADD CONSTRAINT approvvigionamento_fornitore_fkey FOREIGN KEY (fornitore) REFERENCES "Kalunga".fornitore(partita_iva);


--
-- Name: approvvigionamento approvvigionamento_negozio_fkey; Type: FK CONSTRAINT; Schema: Kalunga; Owner: -
--

ALTER TABLE ONLY "Kalunga".approvvigionamento
    ADD CONSTRAINT approvvigionamento_negozio_fkey FOREIGN KEY (negozio) REFERENCES "Kalunga".negozio(id);


--
-- Name: approvvigionamento approvvigionamento_prodotto_fkey; Type: FK CONSTRAINT; Schema: Kalunga; Owner: -
--

ALTER TABLE ONLY "Kalunga".approvvigionamento
    ADD CONSTRAINT approvvigionamento_prodotto_fkey FOREIGN KEY (prodotto) REFERENCES "Kalunga".prodotto(id);


--
-- Name: fornitore_prodotto fornitore_prodotto_fornitore_fkey; Type: FK CONSTRAINT; Schema: Kalunga; Owner: -
--

ALTER TABLE ONLY "Kalunga".fornitore_prodotto
    ADD CONSTRAINT fornitore_prodotto_fornitore_fkey FOREIGN KEY (fornitore) REFERENCES "Kalunga".fornitore(partita_iva);


--
-- Name: fornitore_prodotto fornitore_prodotto_prodotto_fkey; Type: FK CONSTRAINT; Schema: Kalunga; Owner: -
--

ALTER TABLE ONLY "Kalunga".fornitore_prodotto
    ADD CONSTRAINT fornitore_prodotto_prodotto_fkey FOREIGN KEY (prodotto) REFERENCES "Kalunga".prodotto(id);


--
-- Name: negozio negozio_responsabile_fkey; Type: FK CONSTRAINT; Schema: Kalunga; Owner: -
--

ALTER TABLE ONLY "Kalunga".negozio
    ADD CONSTRAINT negozio_responsabile_fkey FOREIGN KEY (responsabile) REFERENCES "Kalunga".utente(id);


--
-- Name: prodotto_negozio prodotto_negozio_negozio_fkey; Type: FK CONSTRAINT; Schema: Kalunga; Owner: -
--

ALTER TABLE ONLY "Kalunga".prodotto_negozio
    ADD CONSTRAINT prodotto_negozio_negozio_fkey FOREIGN KEY (negozio) REFERENCES "Kalunga".negozio(id);


--
-- Name: prodotto_negozio prodotto_negozio_prodotto_fkey; Type: FK CONSTRAINT; Schema: Kalunga; Owner: -
--

ALTER TABLE ONLY "Kalunga".prodotto_negozio
    ADD CONSTRAINT prodotto_negozio_prodotto_fkey FOREIGN KEY (prodotto) REFERENCES "Kalunga".prodotto(id);


--
-- Name: scontrino scontrino_negozio_fkey; Type: FK CONSTRAINT; Schema: Kalunga; Owner: -
--

ALTER TABLE ONLY "Kalunga".scontrino
    ADD CONSTRAINT scontrino_negozio_fkey FOREIGN KEY (negozio) REFERENCES "Kalunga".negozio(id);


--
-- Name: scontrino_prodotto scontrino_prodotto_prodotto_fkey; Type: FK CONSTRAINT; Schema: Kalunga; Owner: -
--

ALTER TABLE ONLY "Kalunga".scontrino_prodotto
    ADD CONSTRAINT scontrino_prodotto_prodotto_fkey FOREIGN KEY (prodotto) REFERENCES "Kalunga".prodotto(id);


--
-- Name: scontrino_prodotto scontrino_prodotto_scontrino_fkey; Type: FK CONSTRAINT; Schema: Kalunga; Owner: -
--

ALTER TABLE ONLY "Kalunga".scontrino_prodotto
    ADD CONSTRAINT scontrino_prodotto_scontrino_fkey FOREIGN KEY (scontrino) REFERENCES "Kalunga".scontrino(id);


--
-- Name: scontrino scontrino_tessera_fkey; Type: FK CONSTRAINT; Schema: Kalunga; Owner: -
--

ALTER TABLE ONLY "Kalunga".scontrino
    ADD CONSTRAINT scontrino_tessera_fkey FOREIGN KEY (tessera) REFERENCES "Kalunga".tessera(id);


--
-- Name: scontrino scontrino_utente_fkey; Type: FK CONSTRAINT; Schema: Kalunga; Owner: -
--

ALTER TABLE ONLY "Kalunga".scontrino
    ADD CONSTRAINT scontrino_utente_fkey FOREIGN KEY (utente) REFERENCES "Kalunga".utente(id);


--
-- Name: tessera tessera_negozio_fkey; Type: FK CONSTRAINT; Schema: Kalunga; Owner: -
--

ALTER TABLE ONLY "Kalunga".tessera
    ADD CONSTRAINT tessera_negozio_fkey FOREIGN KEY (negozio) REFERENCES "Kalunga".negozio(id);


--
-- Name: tessera tessera_utente_fkey; Type: FK CONSTRAINT; Schema: Kalunga; Owner: -
--

ALTER TABLE ONLY "Kalunga".tessera
    ADD CONSTRAINT tessera_utente_fkey FOREIGN KEY (utente) REFERENCES "Kalunga".utente(id);


--
-- PostgreSQL database dump complete
--

