DO $$ 
DECLARE 
    r RECORD;
BEGIN 
    FOR r IN 
        SELECT 
            table_name, 
            column_name, 
            substring(column_default FROM 'nextval\(''(.*)''::regclass\)') AS sequence_name
        FROM 
            information_schema.columns 
        WHERE 
            column_default LIKE 'nextval%'
    LOOP 
        EXECUTE 'SELECT setval(''' || r.sequence_name || ''', COALESCE((SELECT MAX(' || r.column_name || ') FROM ' || r.table_name || '), 1));';
    END LOOP;
END $$;
