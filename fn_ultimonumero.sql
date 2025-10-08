DROP FUNCTION IF EXISTS fn_ultimonumero;
DELIMITER $$
CREATE FUNCTION fn_ultimonumero(placa VARCHAR(20))
RETURNS TEXT
BEGIN
	
	SET @cadena_placa = placa;
	SET @resultado = '';

	IF @cadena_placa <> '' THEN
		my_loop: LOOP
			SET @resultado = RIGHT(@cadena_placa, 1);

			SET @cadena_placa = SUBSTR(@cadena_placa, 1, LENGTH(@cadena_placa)-1);

			IF @resultado BETWEEN '0' AND '9' THEN
				LEAVE my_loop;
			END IF;
            
            IF @cadena_placa = '' THEN
            	SET @resultado = '';
                LEAVE my_loop;
            END IF;

		END LOOP my_loop;
	END IF;

	RETURN @resultado;
END $$
DELIMITER ;