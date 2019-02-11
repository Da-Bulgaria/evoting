package bg.dabulgaria;

import java.io.BufferedReader;
import java.io.FileReader;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.HashMap;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;
import java.util.Map.Entry;

import org.apache.commons.io.Charsets;

import com.fasterxml.jackson.databind.ObjectMapper;
import com.fasterxml.jackson.databind.node.ArrayNode;

public class ResultsCounter {
    
    private static final Map<Integer, Integer> scores = new HashMap<>();
    static {
        scores.put(1, 20);
        scores.put(2, 17);
        scores.put(3, 14);
        scores.put(4, 13);
        scores.put(5, 12);
        scores.put(6, 11);
        scores.put(7, 10);
        scores.put(8, 9);
        scores.put(9, 8);
        scores.put(10, 7);
        scores.put(11, 6);
        scores.put(12, 5);
        scores.put(13, 4);
        scores.put(14, 3);
        scores.put(15, 2);
        scores.put(16, 1);
        scores.put(17, 0);
    }
    
    public static void main(String[] args) throws Exception {
        
        Map<Integer, Integer> result = new LinkedHashMap<>();
        for (int i = 1; i <= 17; i++) {
            result.put(i, 0);
        }
        
        ObjectMapper mapper = new ObjectMapper();
        
        try (BufferedReader r = new BufferedReader(new FileReader(args[0], Charsets.UTF_8))) {
            String line = null;
            while ((line = r.readLine()) != null) {
                try {
                    ArrayNode array = (ArrayNode) mapper.readTree(line).get("vote");
                    for (int i = 0; i < array.size(); i ++) {
                        int candidateNumber = array.get(i).intValue();
                        result.put(candidateNumber, result.get(candidateNumber) + scores.get(i + 1));
                    }
                } catch (Exception ex) {
                    ex.printStackTrace();
                    System.out.println("Failed to read line " + line);
                }
            }
        }
        
        System.out.println("Candidate,Score");
        for (Entry<Integer, Integer> entry : result.entrySet()) {
            System.out.println(entry.getKey() + "," + entry.getValue());
        }
    }
}
